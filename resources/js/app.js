import { Editor, Extension, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

const siteMenu = document.querySelector('.site-menu');
const mobileViewport = window.matchMedia('(max-width: 700px)');

if (siteMenu) {
	const syncMenu = () => {
		siteMenu.open = !mobileViewport.matches;
	};

	syncMenu();
	mobileViewport.addEventListener('change', syncMenu);
}

const siteHeader = document.querySelector('.site-header');

if (siteHeader) {
	const syncHeaderScrollState = () => {
		siteHeader.classList.toggle('is-scrolled', window.scrollY > 8);
	};

	syncHeaderScrollState();
	window.addEventListener('scroll', syncHeaderScrollState, { passive: true });
}

const featuredCarousel = document.querySelector('[data-featured-carousel]');

if (featuredCarousel instanceof HTMLElement) {
	const slides = Array.from(featuredCarousel.querySelectorAll('[data-featured-slide]'));
	const controls = featuredCarousel.querySelector('[data-featured-controls]');
	const position = featuredCarousel.querySelector('[data-featured-position]');
	const previousButton = featuredCarousel.querySelector('[data-featured-previous]');
	const nextButton = featuredCarousel.querySelector('[data-featured-next]');
	const rotationButton = featuredCarousel.querySelector('[data-featured-rotation]');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

	if (
		slides.length > 1
		&& controls instanceof HTMLElement
		&& position instanceof HTMLElement
		&& previousButton instanceof HTMLButtonElement
		&& nextButton instanceof HTMLButtonElement
		&& rotationButton instanceof HTMLButtonElement
	) {
		let activeIndex = 0;
		let rotationTimer;
		let rotationPaused = false;
		const rotationInterval = 6000;

		const updateRotationButton = () => {
			rotationButton.hidden = reducedMotion.matches;
			rotationButton.setAttribute('aria-pressed', String(rotationPaused));
			rotationButton.textContent = rotationPaused ? 'Lanjutkan otomatis' : 'Jeda otomatis';
			featuredCarousel.setAttribute('aria-live', rotationPaused || reducedMotion.matches ? 'polite' : 'off');
		};

		const stopRotation = () => {
			window.clearInterval(rotationTimer);
			rotationTimer = undefined;
		};

		const startRotation = () => {
			stopRotation();

			if (!reducedMotion.matches && !rotationPaused && document.visibilityState === 'visible') {
				rotationTimer = window.setInterval(() => showSlide(activeIndex + 1), rotationInterval);
			}
		};

		const showSlide = (index) => {
			slides[activeIndex].hidden = true;
			slides[activeIndex].classList.remove('is-entering');
			activeIndex = (index + slides.length) % slides.length;
			slides[activeIndex].hidden = false;
			slides[activeIndex].classList.add('is-entering');
			position.textContent = `${activeIndex + 1} / ${slides.length}`;
			startRotation();
		};

		controls.hidden = false;
		updateRotationButton();
		startRotation();

		previousButton.addEventListener('click', () => showSlide(activeIndex - 1));
		nextButton.addEventListener('click', () => showSlide(activeIndex + 1));
		rotationButton.addEventListener('click', () => {
			rotationPaused = !rotationPaused;
			updateRotationButton();
			startRotation();
		});
		featuredCarousel.addEventListener('focusin', () => {
			rotationPaused = true;
			updateRotationButton();
			stopRotation();
		});
		document.addEventListener('visibilitychange', startRotation);
		reducedMotion.addEventListener('change', () => {
			updateRotationButton();
			startRotation();
		});
	}
}

const isSafeEditorLink = (url) => {
	const value = url.trim();

	if (!value || /[\u0000-\u0020]/u.test(value) || value.startsWith('//')) {
		return false;
	}

	if (value.startsWith('/') || value.startsWith('#')) {
		return true;
	}

	const scheme = value.match(/^([a-z][a-z0-9+.-]*):/iu)?.[1].toLowerCase();
	return ['http', 'https', 'mailto'].includes(scheme);
};

const TextAlignment = Extension.create({
	name: 'textAlignment',
	addGlobalAttributes() {
		return [{
			types: ['blockquote', 'heading', 'paragraph'],
			attributes: {
				textAlign: {
					default: null,
					parseHTML: (element) => ['left', 'center', 'right', 'justify'].includes(element.style.textAlign)
						? element.style.textAlign
						: null,
					renderHTML: ({ textAlign }) => ['left', 'center', 'right', 'justify'].includes(textAlign)
						? { style: `text-align: ${textAlign}` }
						: {},
				},
			},
		}];
	},
	addCommands() {
		return {
			setTextAlign: (textAlign) => ({ commands }) => {
				if (!['left', 'center', 'right', 'justify'].includes(textAlign)) {
					return false;
				}

				return ['paragraph', 'heading', 'blockquote']
					.some((type) => commands.updateAttributes(type, { textAlign }));
			},
		};
	},
});

const RichTextImage = Node.create({
	name: 'image',
	group: 'block',
	atom: true,
	draggable: true,
	addAttributes() {
		return {
			src: { default: null },
			alt: { default: '' },
			title: { default: null },
		};
	},
	parseHTML() {
		return [{ tag: 'img[src]' }];
	},
	renderHTML({ HTMLAttributes }) {
		return ['img', HTMLAttributes];
	},
});

document.querySelectorAll('[data-rich-text-editor]').forEach((editorRoot) => {
	if (!(editorRoot instanceof HTMLElement)) {
		return;
	}

	const source = editorRoot.querySelector('[data-rich-text-source]');
	const surface = editorRoot.querySelector('[data-rich-text-surface]');
	const toolbar = editorRoot.querySelector('[data-rich-text-toolbar]');
	const status = editorRoot.querySelector('[data-rich-text-status]');
	const counter = editorRoot.querySelector('[data-rich-text-count]');
	const formatSelect = editorRoot.querySelector('[data-rich-text-format]');
	const imageInput = editorRoot.querySelector('[data-rich-text-image-input]');
	const form = editorRoot.closest('form');
	const maxLength = Number(editorRoot.dataset.richTextMaxLength);
	const imageUploadUrl = editorRoot.dataset.richTextImageUploadUrl;

	if (
		!(source instanceof HTMLTextAreaElement)
		|| !(surface instanceof HTMLElement)
		|| !(toolbar instanceof HTMLElement)
		|| !(status instanceof HTMLElement)
		|| !(counter instanceof HTMLElement)
		|| !(formatSelect instanceof HTMLSelectElement)
		|| !(imageInput instanceof HTMLInputElement)
		|| !(form instanceof HTMLFormElement)
		|| !imageUploadUrl
		|| !Number.isInteger(maxLength)
		|| maxLength < 1
	) {
		return;
	}

	const isRequired = source.required;
	const editorContent = (currentEditor) => currentEditor.isEmpty ? '' : currentEditor.getHTML();
	const updateToolbar = (editor) => {
		const commands = {
			bold: editor.isActive('bold'),
			italic: editor.isActive('italic'),
			underline: editor.isActive('underline'),
			strike: editor.isActive('strike'),
			code: editor.isActive('code'),
			paragraph: editor.isActive('paragraph'),
			heading2: editor.isActive('heading', { level: 2 }),
			heading3: editor.isActive('heading', { level: 3 }),
			blockquote: editor.isActive('blockquote'),
			bulletList: editor.isActive('bulletList'),
			orderedList: editor.isActive('orderedList'),
			alignLeft: editor.isActive('paragraph', { textAlign: 'left' })
				|| editor.isActive('heading', { textAlign: 'left' })
				|| editor.isActive('blockquote', { textAlign: 'left' }),
			alignCenter: editor.isActive('paragraph', { textAlign: 'center' })
				|| editor.isActive('heading', { textAlign: 'center' })
				|| editor.isActive('blockquote', { textAlign: 'center' }),
			alignRight: editor.isActive('paragraph', { textAlign: 'right' })
				|| editor.isActive('heading', { textAlign: 'right' })
				|| editor.isActive('blockquote', { textAlign: 'right' }),
			alignJustify: editor.isActive('paragraph', { textAlign: 'justify' })
				|| editor.isActive('heading', { textAlign: 'justify' })
				|| editor.isActive('blockquote', { textAlign: 'justify' }),
		};

		toolbar.querySelectorAll('[data-rich-text-command][aria-pressed]').forEach((button) => {
			if (!(button instanceof HTMLButtonElement)) {
				return;
			}

			const command = button.dataset.richTextCommand;
			const key = command === 'heading'
				? `heading${button.dataset.richTextLevel}`
				: command === 'align'
					? `align${button.dataset.richTextAlignment[0].toUpperCase()}${button.dataset.richTextAlignment.slice(1)}`
					: command;
			button.setAttribute('aria-pressed', String(commands[key] ?? false));
		});

		formatSelect.value = editor.isActive('heading', { level: 2 })
			? 'heading-2'
			: editor.isActive('heading', { level: 3 })
				? 'heading-3'
				: editor.isActive('heading', { level: 4 })
					? 'heading-4'
					: editor.isActive('blockquote')
						? 'blockquote'
						: 'paragraph';
	};

	const editor = new Editor({
		element: surface,
		content: source.value || '<p></p>',
		extensions: [
			StarterKit.configure({
				heading: { levels: [2, 3, 4] },
				link: {
					openOnClick: false,
					autolink: false,
					linkOnPaste: false,
					isAllowedUri: isSafeEditorLink,
				},
			}),
			TextAlignment,
			RichTextImage,
		],
		editorProps: {
			attributes: {
				class: 'tiptap-editor__content',
				role: 'textbox',
				'aria-multiline': 'true',
				'aria-labelledby': surface.getAttribute('aria-labelledby') ?? '',
				'aria-describedby': surface.getAttribute('aria-describedby') ?? '',
				'aria-required': String(isRequired),
			},
		},
		onUpdate: ({ editor: updatedEditor }) => {
			source.value = editorContent(updatedEditor);
			counter.textContent = `${updatedEditor.getText().length.toLocaleString('id-ID')} / ${maxLength.toLocaleString('id-ID')} karakter`;
			status.hidden = true;
			status.classList.remove('is-success');
			updateToolbar(updatedEditor);
		},
		onSelectionUpdate: ({ editor: updatedEditor }) => updateToolbar(updatedEditor),
	});

	source.required = false;
	source.hidden = true;
	surface.hidden = false;
	toolbar.hidden = false;
	counter.hidden = false;
	source.value = editorContent(editor);
	counter.textContent = `${editor.getText().length.toLocaleString('id-ID')} / ${maxLength.toLocaleString('id-ID')} karakter`;
	updateToolbar(editor);

	toolbar.addEventListener('mousedown', (event) => {
		if (event.target instanceof Element && event.target.closest('button')) {
			event.preventDefault();
		}
	});

	toolbar.addEventListener('click', (event) => {
		if (!(event.target instanceof Element)) {
			return;
		}

		const button = event.target.closest('[data-rich-text-command]');
		if (!(button instanceof HTMLButtonElement)) {
			return;
		}

		const command = button.dataset.richTextCommand;
		const chain = editor.chain().focus();

		switch (command) {
			case 'bold':
				chain.toggleBold().run();
				break;
			case 'italic':
				chain.toggleItalic().run();
				break;
			case 'underline':
				chain.toggleUnderline().run();
				break;
			case 'strike':
				chain.toggleStrike().run();
				break;
			case 'code':
				chain.toggleCode().run();
				break;
			case 'codeBlock':
				chain.toggleCodeBlock().run();
				break;
			case 'paragraph':
				chain.setParagraph().run();
				break;
			case 'heading':
				chain.toggleHeading({ level: Number(button.dataset.richTextLevel) }).run();
				break;
			case 'blockquote':
				chain.toggleBlockquote().run();
				break;
			case 'bulletList':
				chain.toggleBulletList().run();
				break;
			case 'orderedList':
				chain.toggleOrderedList().run();
				break;
			case 'indent':
				chain.sinkListItem('listItem').run();
				break;
			case 'outdent':
				chain.liftListItem('listItem').run();
				break;
			case 'align':
				chain.setTextAlign(button.dataset.richTextAlignment).run();
				break;
			case 'horizontalRule':
				chain.setHorizontalRule().run();
				break;
			case 'clearFormatting':
				chain.clearNodes().unsetAllMarks().run();
				break;
			case 'undo':
				chain.undo().run();
				break;
			case 'redo':
				chain.redo().run();
				break;
			case 'link': {
				if (editor.state.selection.empty) {
					status.textContent = 'Pilih teks yang ingin dijadikan tautan terlebih dahulu.';
					status.classList.remove('is-success');
					status.hidden = false;
					return;
				}

				const url = window.prompt('Masukkan URL tautan');
				if (url === null) {
					return;
				}

				if (!isSafeEditorLink(url)) {
					status.textContent = 'Gunakan tautan relatif, HTTP, HTTPS, atau email yang aman.';
					status.classList.remove('is-success');
					status.hidden = false;
					return;
				}

				chain.extendMarkRange('link').setLink({ href: url.trim() }).run();
				break;
			}
			case 'unlink':
				chain.extendMarkRange('link').unsetLink().run();
				break;
			case 'image': {
				const altText = window.prompt(
					'Tulis keterangan singkat gambar (teks alternatif). Boleh kosong jika gambar hanya hiasan.',
					'',
				);

				if (altText === null) {
					return;
				}

				imageInput.dataset.altText = altText;
				imageInput.click();
				break;
			}
		}
	});

	formatSelect.addEventListener('change', () => {
		const chain = editor.chain().focus();

		switch (formatSelect.value) {
			case 'heading-2':
				chain.setHeading({ level: 2 }).run();
				break;
			case 'heading-3':
				chain.setHeading({ level: 3 }).run();
				break;
			case 'heading-4':
				chain.setHeading({ level: 4 }).run();
				break;
			case 'blockquote':
				chain.setBlockquote().run();
				break;
			default:
				chain.setParagraph().run();
		}
	});

	imageInput.addEventListener('change', async () => {
		const image = imageInput.files?.[0];

		if (!image) {
			return;
		}

		const csrfToken = form.querySelector('input[name="_token"]');
		if (!(csrfToken instanceof HTMLInputElement)) {
			status.textContent = 'Token keamanan form tidak ditemukan. Muat ulang halaman, lalu coba lagi.';
			status.classList.remove('is-success');
			status.hidden = false;
			imageInput.value = '';
			return;
		}

		const imageButton = toolbar.querySelector('[data-rich-text-command="image"]');
		if (imageButton instanceof HTMLButtonElement) {
			imageButton.disabled = true;
			imageButton.textContent = 'Mengunggah gambar…';
		}

		status.textContent = 'Gambar sedang diunggah dan diproses…';
		status.classList.remove('is-success');
		status.hidden = false;
		const body = new FormData();
		body.append('image', image);

		try {
			const response = await fetch(imageUploadUrl, {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-CSRF-TOKEN': csrfToken.value,
				},
				body,
				credentials: 'same-origin',
			});
			let result;

			try {
				result = await response.json();
			} catch {
				throw new Error(`Gambar gagal diunggah (kode ${response.status}). Muat ulang halaman jika sesi sudah berakhir.`);
			}

			if (!response.ok || typeof result.url !== 'string') {
				throw new Error(result.errors?.image?.[0] ?? result.message ?? 'Gambar gagal diunggah. Silakan coba lagi.');
			}

			editor.chain().focus().insertContent({
				type: 'image',
				attrs: { src: result.url, alt: imageInput.dataset.altText ?? '' },
			}).run();
			status.textContent = 'Gambar berhasil disisipkan. Perubahan akan tersimpan saat Anda menyimpan konten.';
			status.classList.add('is-success');
			status.hidden = false;
		} catch (error) {
			status.textContent = error instanceof Error
				? error.message
				: 'Gambar gagal diunggah. Periksa koneksi, lalu coba lagi.';
			status.classList.remove('is-success');
			status.hidden = false;
		} finally {
			imageInput.value = '';
			delete imageInput.dataset.altText;

			if (imageButton instanceof HTMLButtonElement) {
				imageButton.disabled = false;
				imageButton.textContent = 'Sisipkan gambar';
			}
		}
	});

	form.addEventListener('submit', (event) => {
		source.value = editorContent(editor);
		const hasContent = !editor.isEmpty;
		const exceedsLimit = source.value.length > maxLength || editor.getText().length > maxLength;

		if ((isRequired && !hasContent) || exceedsLimit) {
			event.preventDefault();
			status.textContent = exceedsLimit
				? `Konten melebihi batas ${maxLength.toLocaleString('id-ID')} karakter.`
				: 'Konten belum diisi. Tambahkan teks atau gambar terlebih dahulu.';
			status.classList.remove('is-success');
			status.hidden = false;
			editor.commands.focus();
		}
	});
});

const productGallery = document.querySelector('[data-product-gallery]');

if (productGallery instanceof HTMLElement) {
	const mainImage = productGallery.querySelector('[data-product-gallery-main]');
	const thumbnailButtons = productGallery.querySelectorAll('[data-product-gallery-thumb]');

	if (mainImage instanceof HTMLImageElement) {
		thumbnailButtons.forEach((thumbnail) => {
			if (!(thumbnail instanceof HTMLButtonElement)) {
				return;
			}

			thumbnail.addEventListener('click', () => {
				const { src, srcset, alt, width, height } = thumbnail.dataset;

				if (!src || !width || !height) {
					return;
				}

				mainImage.src = src;
				if (srcset) {
					mainImage.srcset = srcset;
				} else {
					mainImage.removeAttribute('srcset');
				}
				mainImage.alt = alt ?? '';
				mainImage.width = Number(width);
				mainImage.height = Number(height);

				thumbnailButtons.forEach((button) => {
					if (button instanceof HTMLButtonElement) {
						button.setAttribute('aria-pressed', String(button === thumbnail));
					}
				});
			});
		});
	}
}

const passwordToggle = document.querySelector('[data-password-toggle]');

if (passwordToggle instanceof HTMLButtonElement) {
	const passwordInput = document.getElementById(passwordToggle.getAttribute('aria-controls'));

	if (passwordInput instanceof HTMLInputElement) {
		passwordToggle.addEventListener('click', () => {
			const isVisible = passwordInput.type === 'text';
			passwordInput.type = isVisible ? 'password' : 'text';
			passwordToggle.setAttribute('aria-pressed', String(!isVisible));
			passwordToggle.setAttribute('aria-label', isVisible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
		});
	}
}

const adminLoginForm = document.querySelector('[data-admin-login-form]');

if (adminLoginForm instanceof HTMLFormElement) {
	const submitButton = adminLoginForm.querySelector('[data-login-submit]');
	const submitLabel = adminLoginForm.querySelector('[data-login-submit-label]');
	let isSubmitting = false;

	adminLoginForm.addEventListener('submit', (event) => {
		if (isSubmitting) {
			event.preventDefault();
			return;
		}

		isSubmitting = true;

		if (submitButton instanceof HTMLButtonElement) {
			submitButton.disabled = true;
			submitButton.setAttribute('aria-busy', 'true');
		}

		if (submitLabel instanceof HTMLElement) {
			submitLabel.textContent = 'Memproses...';
		}
	});
}

const stockAdjustmentForm = document.querySelector('[data-stock-adjustment-form]');

if (stockAdjustmentForm instanceof HTMLFormElement) {
	const submitButton = stockAdjustmentForm.querySelector('[data-stock-adjustment-submit]');
	let isSubmitting = false;

	stockAdjustmentForm.addEventListener('submit', (event) => {
		if (isSubmitting) {
			event.preventDefault();
			return;
		}

		isSubmitting = true;

		if (submitButton instanceof HTMLButtonElement) {
			submitButton.disabled = true;
			submitButton.setAttribute('aria-busy', 'true');
			submitButton.textContent = 'Menyimpan...';
		}
	});
}

const imageOrderForm = document.querySelector('[data-product-image-order-form]');

if (imageOrderForm instanceof HTMLFormElement) {
	const imageList = imageOrderForm.querySelector('[data-product-image-list]');

	if (imageList instanceof HTMLOListElement) {
		const imageCards = () => Array.from(imageList.querySelectorAll('[data-product-image-card]'));

		const synchronizeOrderControls = () => {
			const cards = imageCards();

			cards.forEach((card, index) => {
				const orderInput = card.querySelector('[data-product-image-order]');
				const primaryLabel = card.querySelector('[data-product-image-primary]');
				const moveUpButton = card.querySelector('[data-product-image-move="up"]');
				const moveDownButton = card.querySelector('[data-product-image-move="down"]');
				const primaryButton = card.querySelector('[data-product-image-primary-action]');

				if (orderInput instanceof HTMLInputElement) {
					orderInput.value = String(index);
					orderInput.max = String(cards.length - 1);
				}

				if (primaryLabel instanceof HTMLElement) {
					primaryLabel.hidden = index !== 0;
				}

				if (moveUpButton instanceof HTMLButtonElement) {
					moveUpButton.disabled = index === 0;
					moveUpButton.setAttribute('aria-label', `Pindahkan foto ${index + 1} ke urutan sebelumnya`);
				}

				if (moveDownButton instanceof HTMLButtonElement) {
					moveDownButton.disabled = index === cards.length - 1;
					moveDownButton.setAttribute('aria-label', `Pindahkan foto ${index + 1} ke urutan berikutnya`);
				}

				if (primaryButton instanceof HTMLButtonElement) {
					primaryButton.disabled = index === 0;
					primaryButton.textContent = index === 0 ? 'Foto utama dipilih' : 'Jadikan foto utama';
				}
			});
		};

		const restoredCards = imageCards().sort((firstCard, secondCard) => {
			const firstOrder = Number.parseInt(firstCard.querySelector('[data-product-image-order]')?.value ?? '0', 10);
			const secondOrder = Number.parseInt(secondCard.querySelector('[data-product-image-order]')?.value ?? '0', 10);

			return firstOrder - secondOrder;
		});
		imageList.replaceChildren(...restoredCards);

		const moveCard = (card, targetIndex) => {
			const cards = imageCards();
			const currentIndex = cards.indexOf(card);

			if (currentIndex < 0 || targetIndex < 0 || targetIndex >= cards.length) {
				return;
			}

			cards.splice(currentIndex, 1);
			cards.splice(targetIndex, 0, card);
			imageList.replaceChildren(...cards);
			synchronizeOrderControls();
		};

		imageOrderForm.addEventListener('click', (event) => {
			if (!(event.target instanceof Element)) {
				return;
			}

			const button = event.target.closest('button[data-product-image-move], button[data-product-image-primary-action]');
			const card = button?.closest('[data-product-image-card]');

			if (!(button instanceof HTMLButtonElement) || !card) {
				return;
			}

			const currentIndex = imageCards().indexOf(card);

			if (button.dataset.productImageMove === 'up') {
				moveCard(card, currentIndex - 1);
			} else if (button.dataset.productImageMove === 'down') {
				moveCard(card, currentIndex + 1);
			} else if (button.hasAttribute('data-product-image-primary-action')) {
				moveCard(card, 0);
			}
		});

		imageOrderForm.addEventListener('change', (event) => {
			if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-product-image-order]')) {
				return;
			}

			const requestedIndex = Number.parseInt(event.target.value, 10);

			if (Number.isInteger(requestedIndex)) {
				moveCard(event.target.closest('[data-product-image-card]'), requestedIndex);
			}
		});

		synchronizeOrderControls();
	}
}

const productImageUploadForm = document.querySelector('[data-product-image-upload]');

if (productImageUploadForm instanceof HTMLFormElement) {
	const imageInput = productImageUploadForm.querySelector('[data-product-image-input]');
	const previewList = productImageUploadForm.querySelector('[data-product-image-previews]');
	const submitButton = productImageUploadForm.querySelector('[data-product-image-submit]');
	const csrfInput = productImageUploadForm.querySelector('input[name="_token"]');
	const imageLimit = Number.parseInt(productImageUploadForm.dataset.imageLimit ?? '5', 10);
	const maximumBytes = Number.parseInt(productImageUploadForm.dataset.maxBytes ?? '0', 10);
	let existingCount = Number.parseInt(productImageUploadForm.dataset.existingCount ?? '0', 10);
	let selectedFiles = [];
	let isUploading = false;

	const formatBytes = (bytes) => {
		if (bytes < 1024) {
			return `${bytes} B`;
		}

		const kibibytes = bytes / 1024;

		if (kibibytes < 1024) {
			return `${kibibytes.toFixed(1)} KiB`;
		}

		return `${(kibibytes / 1024).toFixed(2)} MiB`;
	};

	const releasePreviewUrls = () => {
		selectedFiles.forEach((item) => {
			if (item.previewUrl) {
				URL.revokeObjectURL(item.previewUrl);
				item.previewUrl = '';
			}
		});
	};

	const setFileState = (item, state, message) => {
		item.card.dataset.state = state;
		item.status.textContent = message;
		item.error.textContent = state === 'error' ? message : '';
		item.error.hidden = state !== 'error';
	};

	const showSelectedFiles = () => {
		if (!(imageInput instanceof HTMLInputElement) || !(previewList instanceof HTMLElement)) {
			return;
		}

		releasePreviewUrls();
		previewList.replaceChildren();
		selectedFiles = Array.from(imageInput.files ?? []).map((file) => {
			const card = document.createElement('article');
			card.className = 'product-image-preview';
			card.dataset.state = 'ready';

			const image = document.createElement('img');
			image.className = 'product-image-preview__image';
			image.alt = '';
			image.loading = 'lazy';
			image.decoding = 'async';
			image.src = URL.createObjectURL(file);

			const details = document.createElement('div');
			details.className = 'product-image-preview__details';

			const fileName = document.createElement('p');
			fileName.className = 'font-semibold text-brand-dark';
			fileName.textContent = file.name;

			const inputSize = document.createElement('p');
			inputSize.textContent = `Ukuran input: ${formatBytes(file.size)}`;

			const status = document.createElement('p');
			status.className = 'product-image-preview__status mt-1';
			status.setAttribute('role', 'status');
			status.setAttribute('aria-live', 'polite');
			status.textContent = file.size > maximumBytes ? 'Gagal: ukuran file melebihi batas.' : 'Siap diunggah.';

			const error = document.createElement('p');
			error.className = 'product-image-preview__error mt-1';
			error.setAttribute('role', 'alert');
			error.hidden = true;

			const progress = document.createElement('progress');
			progress.max = 100;
			progress.value = 0;
			progress.setAttribute('aria-label', `Progres unggah ${file.name}`);

			details.append(fileName, inputSize, status, error, progress);
			card.append(image, details);
			previewList.append(card);

			return {
				file,
				previewUrl: image.src,
				image,
				card,
				status,
				error,
				progress,
				completed: false,
				invalid: file.size > maximumBytes,
			};
		});

		const availableSlots = Math.max(0, imageLimit - existingCount);

		if (selectedFiles.length > availableSlots) {
			selectedFiles.forEach((item) => setFileState(item, 'error', `Melebihi batas: hanya ${availableSlots} slot foto tersisa.`));
		} else {
			selectedFiles.filter((item) => item.invalid).forEach((item) => {
				setFileState(item, 'error', `Ukuran file ${formatBytes(item.file.size)} melebihi batas ${formatBytes(maximumBytes)}.`);
			});
		}
	};

	const uploadFile = (item) => new Promise((resolve) => {
		const request = new XMLHttpRequest();
		const data = new FormData();
		const formAction = productImageUploadForm.getAttribute('action');
		const showUncertainError = (message) => {
			item.uncertain = true;
			setFileState(item, 'error', message);

			const refreshLink = document.createElement('a');
			refreshLink.className = 'ml-1 underline underline-offset-4';
			refreshLink.href = window.location.href;
			refreshLink.textContent = 'Muat ulang dan periksa galeri';
			item.error.append(' ', refreshLink);
		};

		if (!formAction || !(csrfInput instanceof HTMLInputElement)) {
			setFileState(item, 'error', 'Form unggah tidak lengkap. Muat ulang halaman.');
			resolve(false);
			return;
		}

		data.append('_token', csrfInput.value);
		data.append('images[0]', item.file, item.file.name);
		setFileState(item, 'uploading', 'Menyiapkan unggahan...');
		item.progress.value = 0;
		request.open('POST', formAction);
		request.setRequestHeader('Accept', 'application/json');
		request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		request.timeout = 120000;

		request.upload.addEventListener('progress', (event) => {
			if (event.lengthComputable) {
				const percent = Math.round((event.loaded / event.total) * 100);
				item.progress.value = percent;
				setFileState(item, 'uploading', `Mengunggah: ${percent}%`);
			} else {
				setFileState(item, 'uploading', 'Mengunggah...');
			}
		});

		request.addEventListener('load', () => {
			let response;

			try {
				response = JSON.parse(request.responseText);
			} catch {
				showUncertainError('Status unggahan tidak dapat dipastikan.');
				resolve(false);
				return;
			}

			if (request.status >= 500) {
				showUncertainError('Server gagal merespons dan status unggahan tidak dapat dipastikan.');
				resolve(false);
				return;
			}

			if (request.status < 200 || request.status >= 300) {
				const messages = Object.values(response.errors ?? {}).flat();
				setFileState(item, 'error', messages.join(' ') || response.message || 'Unggahan gagal. Periksa file dan coba lagi.');
				resolve(false);
				return;
			}

			const storedImage = response.images?.[0];

			if (!storedImage || !Number.isInteger(storedImage.input_size) || !Number.isInteger(storedImage.stored_size)) {
				showUncertainError('Unggahan mungkin tersimpan, tetapi metadata hasil tidak tersedia.');
				resolve(false);
				return;
			}

			item.completed = true;
			item.card.dataset.state = 'success';
			item.progress.value = 100;
			item.image.removeAttribute('src');
			item.image.hidden = true;
			URL.revokeObjectURL(item.previewUrl);
			item.previewUrl = '';
			item.status.textContent = `Tersimpan: ${storedImage.width} × ${storedImage.height} px, ${storedImage.mime_type}.`;

			const storedSize = document.createElement('p');
			storedSize.textContent = `Ukuran hasil tersimpan (semua varian): ${formatBytes(storedImage.stored_size)}. Ukuran input terverifikasi server: ${formatBytes(storedImage.input_size)}.`;
			item.status.after(storedSize);

			const refreshLink = document.createElement('a');
			refreshLink.className = 'mt-1 inline-block text-brand underline underline-offset-4';
			refreshLink.href = window.location.href;
			refreshLink.textContent = 'Muat ulang untuk mengatur foto di galeri';
			storedSize.after(refreshLink);
			existingCount += 1;
			resolve(true);
		});

		request.addEventListener('error', () => {
			showUncertainError('Status unggahan tidak dapat dipastikan.');
			resolve(false);
		});
		request.addEventListener('timeout', () => {
			showUncertainError('Status unggahan tidak dapat dipastikan.');
			resolve(false);
		});
		request.send(data);
	});

	if (imageInput instanceof HTMLInputElement && previewList instanceof HTMLElement && submitButton instanceof HTMLButtonElement) {
		imageInput.addEventListener('change', showSelectedFiles);

		productImageUploadForm.addEventListener('submit', async (event) => {
			event.preventDefault();

			if (isUploading) {
				return;
			}

			if (selectedFiles.length === 0) {
				showSelectedFiles();
			}

			if (selectedFiles.length === 0) {
				return;
			}

			const availableSlots = Math.max(0, imageLimit - existingCount);

			if (selectedFiles.length > availableSlots) {
				selectedFiles.forEach((item) => setFileState(item, 'error', `Melebihi batas: hanya ${availableSlots} slot foto tersisa.`));
				return;
			}

			isUploading = true;
			submitButton.disabled = true;
			submitButton.setAttribute('aria-busy', 'true');

			try {
				for (const item of selectedFiles) {
					if (item.completed || item.invalid || item.uncertain) {
						continue;
					}

					await uploadFile(item);
				}
			} finally {
				isUploading = false;
				submitButton.disabled = false;
				submitButton.removeAttribute('aria-busy');
			}
		});

		window.addEventListener('pagehide', releasePreviewUrls, { once: true });
	}
}
