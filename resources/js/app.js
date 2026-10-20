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

const articleEditor = document.querySelector('[data-article-editor]');

if (articleEditor instanceof HTMLElement) {
	const source = articleEditor.querySelector('[data-article-editor-source]');
	const surface = articleEditor.querySelector('[data-article-editor-surface]');
	const toolbar = articleEditor.querySelector('[data-article-editor-toolbar]');
	const tabs = articleEditor.querySelector('[data-article-editor-tabs]');
	const label = articleEditor.querySelector('[data-article-editor-label]');
	const status = articleEditor.querySelector('[data-article-editor-status]');
	const counter = articleEditor.querySelector('[data-article-editor-count]');
	const form = articleEditor.closest('form');
	const allowedElements = new Set(['a', 'blockquote', 'br', 'code', 'em', 'h2', 'h3', 'h4', 'hr', 'i', 'li', 'ol', 'p', 'pre', 'strong', 'u', 'ul']);
	const removedElements = new Set(['embed', 'iframe', 'math', 'object', 'script', 'style', 'svg', 'template']);
	const maximumLength = 50000;

	if (
		source instanceof HTMLTextAreaElement
		&& surface instanceof HTMLElement
		&& toolbar instanceof HTMLElement
		&& tabs instanceof HTMLElement
		&& label instanceof HTMLLabelElement
		&& status instanceof HTMLElement
		&& counter instanceof HTMLElement
		&& form instanceof HTMLFormElement
	) {
		const isSafeLink = (url) => {
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

		const appendSafeNodes = (sourceNodes, target) => {
			sourceNodes.forEach((node) => {
				if (node.nodeType === Node.TEXT_NODE) {
					target.append(document.createTextNode(node.textContent ?? ''));
					return;
				}

				if (!(node instanceof HTMLElement)) {
					return;
				}

				const tag = node.tagName.toLowerCase();
				if (removedElements.has(tag)) {
					return;
				}
				if (tag === 'b') {
					const strongElement = document.createElement('strong');
					appendSafeNodes(Array.from(node.childNodes), strongElement);
					target.append(strongElement);
					return;
				}

				if (!allowedElements.has(tag)) {
					appendSafeNodes(Array.from(node.childNodes), target);
					return;
				}

				const safeElement = document.createElement(tag);
				if (tag === 'a') {
					const href = node.getAttribute('href');
					const title = node.getAttribute('title');

					if (href && isSafeLink(href)) {
						safeElement.setAttribute('href', href);
					}
					if (title) {
						safeElement.setAttribute('title', title);
					}
				}

				appendSafeNodes(Array.from(node.childNodes), safeElement);
				target.append(safeElement);
			});
		};

		const safeInitialContent = (html) => {
			const template = document.createElement('template');
			template.innerHTML = html;
			const safeContent = document.createDocumentFragment();
			appendSafeNodes(Array.from(template.content.childNodes), safeContent);
			return safeContent;
		};

		const synchronizeSource = () => {
			source.value = surface.innerHTML;
			counter.textContent = `${surface.textContent?.length ?? 0} / ${maximumLength.toLocaleString('id-ID')} karakter`;
			counter.hidden = false;
		};

		const activateTab = (name, focusTab = false) => {
			tabs.querySelectorAll('[data-article-editor-tab]').forEach((tab) => {
				if (!(tab instanceof HTMLButtonElement)) {
					return;
				}

				const isActive = tab.dataset.articleEditorTab === name;
				tab.classList.toggle('is-active', isActive);
				tab.setAttribute('aria-selected', String(isActive));
				tab.tabIndex = isActive ? 0 : -1;
				if (isActive && focusTab) {
					tab.focus();
				}
			});

			toolbar.querySelectorAll('[data-article-editor-panel]').forEach((panel) => {
				panel.hidden = panel.dataset.articleEditorPanel !== name;
			});
		};

		let savedRange = null;
		const rememberSelection = () => {
			const selection = window.getSelection();
			const anchorNode = selection?.anchorNode;
			const focusNode = selection?.focusNode;
			const anchorInside = anchorNode === surface || (anchorNode instanceof Node && surface.contains(anchorNode));
			const focusInside = focusNode === surface || (focusNode instanceof Node && surface.contains(focusNode));

			if (selection?.rangeCount && anchorInside && focusInside) {
				savedRange = selection.getRangeAt(0).cloneRange();
			}
		};

		const focusAtSavedSelection = () => {
			surface.focus();
			const selection = window.getSelection();

			if (savedRange && selection) {
				selection.removeAllRanges();
				selection.addRange(savedRange);
			}
		};

		const updateFormatButtons = () => {
			toolbar.querySelectorAll('[data-article-editor-toggle]').forEach((button) => {
				if (button instanceof HTMLButtonElement) {
					button.setAttribute('aria-pressed', String(document.queryCommandState(button.dataset.articleEditorCommand)));
				}
			});
		};

		surface.replaceChildren(safeInitialContent(source.value));
		document.execCommand('defaultParagraphSeparator', false, 'p');
		source.required = false;
		source.hidden = true;
		surface.hidden = false;
		toolbar.hidden = false;
		counter.hidden = false;
		label.htmlFor = surface.id;
		synchronizeSource();
		activateTab('home');

		document.addEventListener('selectionchange', () => {
			rememberSelection();
			if (document.activeElement === surface) {
				updateFormatButtons();
			}
		});

		surface.addEventListener('keyup', updateFormatButtons);
		surface.addEventListener('mouseup', updateFormatButtons);

		tabs.addEventListener('click', (event) => {
			if (!(event.target instanceof Element)) {
				return;
			}

			const tab = event.target.closest('[data-article-editor-tab]');
			if (tab instanceof HTMLButtonElement) {
				activateTab(tab.dataset.articleEditorTab);
			}
		});

		tabs.addEventListener('keydown', (event) => {
			const tabButtons = Array.from(tabs.querySelectorAll('[data-article-editor-tab]'));
			const currentIndex = tabButtons.indexOf(document.activeElement);
			let nextIndex = currentIndex;

			if (event.key === 'ArrowRight') {
				nextIndex = (currentIndex + 1) % tabButtons.length;
			} else if (event.key === 'ArrowLeft') {
				nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
			} else if (event.key === 'Home') {
				nextIndex = 0;
			} else if (event.key === 'End') {
				nextIndex = tabButtons.length - 1;
			} else {
				return;
			}

			event.preventDefault();
			activateTab(tabButtons[nextIndex].dataset.articleEditorTab, true);
		});

		surface.addEventListener('input', () => {
			normalizeBoldElements();
			synchronizeSource();
			status.hidden = true;
		});

		const normalizeBoldElements = () => {
			surface.querySelectorAll('b').forEach((boldElement) => {
				const strongElement = document.createElement('strong');
				strongElement.replaceChildren(...Array.from(boldElement.childNodes));
				boldElement.replaceWith(strongElement);
			});
		};

		toolbar.addEventListener('mousedown', (event) => {
			if (event.target instanceof Element && event.target.closest('button')) {
				event.preventDefault();
			}
		});

		toolbar.addEventListener('click', (event) => {
			if (!(event.target instanceof Element)) {
				return;
			}

			const button = event.target.closest('[data-article-editor-command]');
			if (!(button instanceof HTMLButtonElement)) {
				return;
			}

			const command = button.dataset.articleEditorCommand;
			let value = button.dataset.articleEditorValue ?? null;
			if (command === 'createLink') {
				const url = window.prompt('Masukkan URL tautan');
				if (!url) {
					return;
				}
				if (!isSafeLink(url)) {
					status.textContent = 'Gunakan tautan relatif, HTTP, HTTPS, atau email yang aman.';
					status.hidden = false;
					return;
				}
				value = url.trim();
			}

			focusAtSavedSelection();
			document.execCommand(command, false, value);
			normalizeBoldElements();
			synchronizeSource();
			status.hidden = true;
			rememberSelection();
			updateFormatButtons();
		});

		const formatSelect = toolbar.querySelector('[data-article-editor-format]');
		if (formatSelect instanceof HTMLSelectElement) {
			formatSelect.addEventListener('change', () => {
				focusAtSavedSelection();
				document.execCommand('formatBlock', false, formatSelect.value);
				synchronizeSource();
				rememberSelection();
			});
		}

		form.addEventListener('submit', (event) => {
			synchronizeSource();
			const serializedLength = source.value.length;
			const hasText = Boolean(surface.textContent?.trim());

			if (!hasText || serializedLength > maximumLength) {
				event.preventDefault();
				status.textContent = hasText
					? 'Isi artikel terlalu panjang. Kurangi beberapa bagian sebelum menyimpan.'
					: 'Isi artikel belum diisi.';
				status.hidden = false;
				surface.focus();
			}
		});
	}
}

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
