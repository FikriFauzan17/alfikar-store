<?php

use App\Http\Controllers\Admin\AccountSecurityController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InformationPageController as AdminInformationPageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductStatisticsController;
use App\Http\Controllers\Admin\RichTextImageController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InformationPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WhatsAppOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/produk', [CatalogController::class, 'index'])
    ->middleware('throttle:public-catalog')
    ->name('products.index');
Route::get('/produk/{product:slug}/whatsapp', [WhatsAppOrderController::class, 'redirect'])
    ->middleware('throttle:whatsapp-order')
    ->name('products.whatsapp');
Route::get('/produk/{product}', [CatalogController::class, 'show'])
    ->middleware('throttle:public-product')
    ->name('products.show');
Route::get('/tentang', [ProfileController::class, 'about'])->name('pages.about');
Route::get('/kontak', [ProfileController::class, 'contact'])->name('pages.contact');
Route::get('/{page}', [InformationPageController::class, 'show'])
    ->whereIn('page', ['kebijakan-privasi', 'ketentuan-pemesanan'])
    ->name('pages.information');
Route::get('/artikel', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/artikel/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
Route::post('/admin/login', [LoginController::class, 'store'])
    ->middleware('throttle:admin-login')
    ->name('admin.login');
Route::get('/admin/login/two-factor', [LoginController::class, 'challenge'])->name('admin.two-factor.challenge');
Route::post('/admin/login/two-factor', [LoginController::class, 'verifyTwoFactor'])
    ->middleware('throttle:admin-two-factor')
    ->name('admin.two-factor.verify');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/statistics/products', ProductStatisticsController::class)->name('statistics.products');
    Route::get('/information-pages', [AdminInformationPageController::class, 'index'])->name('information-pages.index');
    Route::post('/rich-text/images', [RichTextImageController::class, 'store'])
        ->middleware('throttle:product-images')
        ->name('rich-text.images.store');
    Route::get('/information-pages/{page}', [AdminInformationPageController::class, 'edit'])
        ->whereIn('page', ['kebijakan-privasi', 'ketentuan-pemesanan'])
        ->name('information-pages.edit');
    Route::get('/information-pages/{page}/preview', [AdminInformationPageController::class, 'preview'])
        ->whereIn('page', ['kebijakan-privasi', 'ketentuan-pemesanan'])
        ->name('information-pages.preview');
    Route::put('/information-pages/{page}', [AdminInformationPageController::class, 'update'])
        ->whereIn('page', ['kebijakan-privasi', 'ketentuan-pemesanan'])
        ->name('information-pages.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/security', [AccountSecurityController::class, 'index'])->name('security.index');
    Route::post('/security/two-factor/setup', [AccountSecurityController::class, 'startSetup'])->name('security.two-factor.setup');
    Route::post('/security/two-factor/enable', [AccountSecurityController::class, 'enable'])->name('security.two-factor.enable');
    Route::post('/security/two-factor/cancel', [AccountSecurityController::class, 'cancelSetup'])->name('security.two-factor.cancel');
    Route::post('/security/two-factor/disable', [AccountSecurityController::class, 'disable'])->name('security.two-factor.disable');
    Route::post('/security/two-factor/recovery-codes', [AccountSecurityController::class, 'regenerateRecoveryCodes'])
        ->name('security.two-factor.recovery-codes');
    Route::delete('/security/sessions/{sessionId}', [AccountSecurityController::class, 'destroySession'])
        ->where('sessionId', '[A-Za-z0-9,-]+')
        ->name('security.sessions.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');

    Route::get('/products/{product}/preview', [CatalogController::class, 'preview'])->name('products.preview');
    Route::get('/products/{product}/stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->name('products.stock-adjustments.index');
    Route::get('/products/{product}/stock-adjustments/create', [StockAdjustmentController::class, 'create'])
        ->name('products.stock-adjustments.create');
    Route::post('/products/{product}/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->name('products.stock-adjustments.store');
    Route::get('/articles/{article}/preview', [ArticleController::class, 'preview'])->name('articles.preview');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('articles', AdminArticleController::class)->except('show');
    Route::resource('banners', BannerController::class)->except('show');
    Route::get('/articles/{article}/delete-confirmation', [AdminArticleController::class, 'confirmDelete'])
        ->name('articles.confirm-delete');
    Route::get('/banners/{banner}/delete-confirmation', [BannerController::class, 'confirmDelete'])
        ->name('banners.confirm-delete');
    Route::get('/settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SiteSettingController::class, 'update'])->name('settings.update');
    Route::put('/settings/homepage-draft', [SiteSettingController::class, 'saveHomepageDraft'])
        ->name('settings.homepage-draft');
    Route::get('/settings/homepage-preview', [HomeController::class, 'preview'])
        ->name('settings.homepage-preview');

    Route::get('/categories/{category}/delete-confirmation', [CategoryController::class, 'confirmDelete'])
        ->name('categories.confirm-delete');
    Route::get('/products/{product}/delete-confirmation', [ProductController::class, 'confirmDelete'])
        ->name('products.confirm-delete');
    Route::get('/products/{product}/images/{image}/delete-confirmation', [ProductImageController::class, 'confirmDelete'])
        ->scopeBindings()
        ->name('products.images.confirm-delete');

    Route::post('/products/{product}/images', [ProductImageController::class, 'store'])
        ->middleware('throttle:product-images')
        ->name('products.images.store');
    Route::patch('/products/{product}/images/order', [ProductImageController::class, 'updateOrder'])
        ->name('products.images.order');
    Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
        ->scopeBindings()
        ->name('products.images.destroy');
});
