<?php

use App\Http\Controllers\Admin\Ecommerce\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\Content\BlogCategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Admin\Content\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\Ecommerce\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CRM\ContactLeadController as AdminContactLeadController;
use App\Http\Controllers\Admin\Ecommerce\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Dashboard\DashboardExportController;
use App\Http\Controllers\Admin\Ecommerce\IngredientCategoryController as AdminIngredientCategoryController;
use App\Http\Controllers\Admin\Ecommerce\IngredientController as AdminIngredientController;
use App\Http\Controllers\Admin\Marketing\NewsletterSubscriberController as AdminNewsletterSubscriberController;
use App\Http\Controllers\Admin\Nutrition\SubscriptionPlanController;
use App\Http\Controllers\Admin\Ecommerce\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\Ecommerce\ProductController as AdminProductController;
use App\Http\Controllers\Admin\Ecommerce\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Admin\Support\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\Ecommerce\TaxRateController as AdminTaxRateController;
use App\Http\Controllers\Frontend\Account\UserInvoiceController;
use Illuminate\Support\Facades\Route;

// ── Dashboard & Analytics ─────────────────────────────────────────────────────
Route::middleware(['auth:admin', 'permission:view dashboard|manage dashboard,admin'])->controller(DashboardController::class)->group(function () {
    Route::get('/admin', 'index')->name('admin.index');

    Route::middleware('permission:view analytics|manage analytics,admin')->group(function () {
        Route::get('/admin/analytics', 'analytics')->name('admin.analytics');
        Route::get('/admin/analytics/table/{table}', 'analyticsTable')->name('admin.analytics.table');
    });
});

Route::get('/admin/export', DashboardExportController::class)
    ->middleware(['auth:admin', 'permission:view dashboard|manage dashboard,admin'])
    ->name('admin.dashboard.export');

// ── Access Control (Users & Roles) ───────────────────────────────────────────
Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
    // Both users and roles are managed on the same index page. The user needs view access to either.
    Route::middleware('permission:view users|manage users|view roles|manage roles,admin')->group(function () {
        Route::get('access-control', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'index'])->name('access-control.index');
    });

    Route::middleware('permission:manage roles,admin')->group(function () {
        Route::post('access-control/roles', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'storeRole'])->name('access-control.roles.store');
        Route::put('access-control/roles/{role}', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'updateRole'])->name('access-control.roles.update');
        Route::delete('access-control/roles/{role}', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'destroyRole'])->name('access-control.roles.destroy');
    });

    Route::middleware('permission:manage users,admin')->group(function () {
        Route::post('access-control/users', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'storeUser'])->name('access-control.users.store');
        Route::put('access-control/users/{admin}', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'updateUser'])->name('access-control.users.update');
        Route::delete('access-control/users/{admin}', [\App\Http\Controllers\Admin\Access\AccessControlController::class, 'destroyUser'])->name('access-control.users.destroy');
    });
});

// ── Ecommerce Modules ────────────────────────────────────────────────────────
Route::prefix('admin/ecommerce')->name('admin.ecommerce.')->middleware('auth:admin')->group(function () {

    // Register static /create paths before resource {id} show paths.
    Route::middleware('permission:manage products,admin')->group(function () {
        Route::get('categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::get('attributes/create', [AdminAttributeController::class, 'create'])->name('attributes.create');
        Route::get('ingredient-categories/create', [AdminIngredientCategoryController::class, 'create'])->name('ingredient-categories.create');
        Route::get('products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::get('ingredients/create', [AdminIngredientController::class, 'create'])->name('ingredients.create');
        Route::get('variants/create', [AdminProductVariantController::class, 'create'])->name('variants.create');
        Route::get('tax-rates/create', [AdminTaxRateController::class, 'create'])->name('tax-rates.create');
    });
    Route::middleware('permission:manage diet chart,admin')->get('diet-plans/create', [SubscriptionPlanController::class, 'create'])->name('diet-plans.create');
    Route::middleware('permission:manage promotions,admin')->group(function () {
        Route::get('coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
        Route::get('newsletter/create', [AdminNewsletterSubscriberController::class, 'create'])->name('newsletter.create');
        Route::get('contact-leads/create', [AdminContactLeadController::class, 'create'])->name('contact-leads.create');
    });
    Route::middleware('permission:manage content,admin')->group(function () {
        Route::get('blog-categories/create', [AdminBlogCategoryController::class, 'create'])->name('blog-categories.create');
        Route::get('blog-posts/create', [AdminBlogPostController::class, 'create'])->name('blog-posts.create');
    });
    Route::middleware('permission:manage customers,admin')->group(function () {
        Route::get('customers/create', [\App\Http\Controllers\Admin\CRM\CustomerController::class, 'create'])->name('customers.create');
        Route::get('support-tickets/create', [AdminSupportTicketController::class, 'create'])->name('support-tickets.create');
    });
    Route::middleware('permission:manage moderation,admin')->group(function () {
        Route::get('reviews/create', [\App\Http\Controllers\Admin\Content\ReviewController::class, 'create'])->name('reviews.create');
        Route::get('video-reviews/create', [\App\Http\Controllers\Admin\Content\VideoReviewController::class, 'create'])->name('video-reviews.create');
    });
    Route::middleware('permission:manage orders,admin')->group(function () {
        Route::get('order-returns/create', [\App\Http\Controllers\Admin\Ecommerce\OrderReturnController::class, 'create'])->name('order-returns.create');
        Route::get('invoices/create', [\App\Http\Controllers\Admin\Billing\InvoiceController::class, 'create'])->name('invoices.create');
    });
    
    // -- Products Management --
    Route::middleware('permission:view products|manage products,admin')->group(function () {
        Route::resource('categories', AdminCategoryController::class)->only(['index', 'show']);
        Route::resource('attributes', AdminAttributeController::class)->only(['index', 'show']);
        Route::resource('ingredient-categories', AdminIngredientCategoryController::class)->only(['index', 'show']);
        Route::get('products-trash', [AdminProductController::class, 'trash'])->name('products.trash');
        Route::get('products/problem-solution', [AdminProductController::class, 'problemSolutionIndex'])->name('products.problem-solution.index');
        Route::resource('products', AdminProductController::class)->only(['index', 'show']);
        Route::resource('ingredients', AdminIngredientController::class)->only(['index', 'show']);
        Route::resource('variants', AdminProductVariantController::class)->only(['index', 'show']);
        Route::resource('tax-rates', AdminTaxRateController::class)->only(['index', 'show']);
    });
    
    Route::middleware('permission:manage products,admin')->group(function () {
        Route::resource('categories', AdminCategoryController::class)->except(['index', 'show']);
        Route::resource('attributes', AdminAttributeController::class)->except(['index', 'show']);
        Route::resource('ingredient-categories', AdminIngredientCategoryController::class)->except(['index', 'show']);
        
        Route::delete('products-trash/bulk-force-delete', [AdminProductController::class, 'bulkForceDestroy'])->name('products.bulk-force-destroy');
        Route::patch('products-trash/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
        Route::delete('products-trash/{product}/force-delete', [AdminProductController::class, 'forceDestroy'])->name('products.force-destroy');
        Route::get('products/{product}/problem-solution', [AdminProductController::class, 'problemSolution'])->name('products.problem-solution.edit');
        Route::post('products/{product}/problem-solution', [AdminProductController::class, 'updateProblemSolution'])->name('products.problem-solution.update');
        Route::resource('products', AdminProductController::class)->except(['index', 'show']);
        Route::patch('products/{product}/inventory', [AdminProductController::class, 'updateInventory'])->name('products.inventory.update');
        Route::delete('products/images/{image}', [AdminProductController::class, 'deleteImage'])->name('products.images.destroy');
        
        Route::resource('ingredients', AdminIngredientController::class)->except(['index', 'show']);
        Route::resource('variants', AdminProductVariantController::class)->except(['index', 'show']);
        Route::patch('variants/{variant}/inventory', [AdminProductVariantController::class, 'updateInventory'])->name('variants.inventory.update');
        Route::resource('tax-rates', AdminTaxRateController::class)->except(['index', 'show']);
    });

    // -- Diet Chart --
    Route::middleware('permission:view diet chart|manage diet chart,admin')->group(function () {
        Route::resource('diet-plans', SubscriptionPlanController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage diet chart,admin')->group(function () {
        Route::resource('diet-plans', SubscriptionPlanController::class)->except(['index', 'show']);
    });

    // -- Promotions --
    Route::middleware('permission:view promotions|manage promotions,admin')->group(function () {
        Route::resource('coupons', AdminCouponController::class)->only(['index', 'show']);
        Route::get('newsletter/export', [AdminNewsletterSubscriberController::class, 'export'])->name('newsletter.export');
        Route::resource('newsletter', AdminNewsletterSubscriberController::class)->only(['index', 'show']);
        Route::resource('contact-leads', AdminContactLeadController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage promotions,admin')->group(function () {
        Route::resource('coupons', AdminCouponController::class)->except(['index', 'show']);
        Route::resource('newsletter', AdminNewsletterSubscriberController::class)->except(['index', 'show']);
        Route::resource('contact-leads', AdminContactLeadController::class)->except(['index', 'show']);
    });

    // -- Content Management --
    Route::middleware('permission:view content|manage content,admin')->group(function () {
        Route::resource('blog-categories', AdminBlogCategoryController::class)->only(['index', 'show']);
        Route::get('blog-posts-trash', [AdminBlogPostController::class, 'trash'])->name('blog-posts.trash');
        Route::resource('blog-posts', AdminBlogPostController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage content,admin')->group(function () {
        Route::resource('blog-categories', AdminBlogCategoryController::class)->except(['index', 'show']);
        Route::delete('blog-posts-trash/bulk-force-delete', [AdminBlogPostController::class, 'bulkForceDestroy'])->name('blog-posts.bulk-force-destroy');
        Route::patch('blog-posts-trash/{blogPost}/restore', [AdminBlogPostController::class, 'restore'])->name('blog-posts.restore');
        Route::delete('blog-posts-trash/{blogPost}/force-delete', [AdminBlogPostController::class, 'forceDestroy'])->name('blog-posts.force-destroy');
        Route::post('blog-posts/bulk-destroy', [AdminBlogPostController::class, 'bulkForceDestroy'])->name('blog-posts.bulk-destroy');
        Route::post('blog-posts/upload-image', [\App\Http\Controllers\Admin\Content\BlogPostImageController::class, 'upload'])->name('blog-posts.upload-image');
        Route::resource('blog-posts', AdminBlogPostController::class)->except(['index', 'show']);
    });

    // -- Customers & Support --
    Route::middleware('permission:view customers|manage customers,admin')->group(function () {
        Route::resource('customers', \App\Http\Controllers\Admin\CRM\CustomerController::class)->only(['index', 'show']);
        Route::resource('support-tickets', AdminSupportTicketController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage customers,admin')->group(function () {
        Route::resource('customers', \App\Http\Controllers\Admin\CRM\CustomerController::class)->except(['index', 'show']);
        Route::resource('support-tickets', AdminSupportTicketController::class)->except(['index', 'show']);
        Route::post('support-tickets/{support_ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('support-tickets.reply');
    });

    // -- Moderation --
    Route::middleware('permission:view moderation|manage moderation,admin')->group(function () {
        Route::resource('reviews', \App\Http\Controllers\Admin\Content\ReviewController::class)->only(['index', 'show']);
        Route::resource('video-reviews', \App\Http\Controllers\Admin\Content\VideoReviewController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage moderation,admin')->group(function () {
        Route::resource('reviews', \App\Http\Controllers\Admin\Content\ReviewController::class)->except(['index', 'show']);
        Route::resource('video-reviews', \App\Http\Controllers\Admin\Content\VideoReviewController::class)->except(['index', 'show']);
    });

    // -- Orders & Invoices --
    Route::middleware('permission:view orders|manage orders,admin')->group(function () {
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/invoice-download', [UserInvoiceController::class, 'download'])->name('orders.invoice-download');
        Route::resource('order-returns', \App\Http\Controllers\Admin\Ecommerce\OrderReturnController::class)->only(['index', 'show']);
        Route::resource('invoices', \App\Http\Controllers\Admin\Billing\InvoiceController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:manage orders,admin')->group(function () {
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::resource('order-returns', \App\Http\Controllers\Admin\Ecommerce\OrderReturnController::class)->except(['index', 'show']);
        Route::resource('invoices', \App\Http\Controllers\Admin\Billing\InvoiceController::class)->except(['index', 'show']); // typically invoices aren't edited but preserving existing routes
    });

    // -- Loyalty --
    Route::prefix('loyalty')->name('loyalty.')->group(function () {
        Route::middleware('permission:view loyalty|manage loyalty,admin')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\Marketing\LoyaltyController::class, 'settings'])->name('settings');
            Route::get('/transactions', [\App\Http\Controllers\Admin\Marketing\LoyaltyController::class, 'transactions'])->name('transactions');
        });

        Route::middleware('permission:manage loyalty,admin')->group(function () {
            Route::post('/settings', [\App\Http\Controllers\Admin\Marketing\LoyaltyController::class, 'updateSettings'])->name('settings.update');
        });
    });

    // -- Administration (Notifications, Settings, Side Section) --
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::middleware('permission:view administration|manage administration,admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\System\NotificationController::class, 'index'])->name('index');
        });
        Route::middleware('permission:manage administration,admin')->group(function () {
            Route::post('/{id}/read', [\App\Http\Controllers\Admin\System\NotificationController::class, 'markAsRead'])->name('read');
            Route::post('/read-all', [\App\Http\Controllers\Admin\System\NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\System\NotificationController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('permission:view administration|manage administration,admin')->group(function () {
            Route::get('/general', [\App\Http\Controllers\Admin\System\GeneralSettingController::class, 'index'])->name('general');
            Route::get('/payment-gateways', [\App\Http\Controllers\Admin\System\GeneralSettingController::class, 'paymentGateways'])->name('payment-gateways');
            Route::get('/auth', [\App\Http\Controllers\Admin\System\GeneralSettingController::class, 'authSettings'])->name('auth');
        });
        Route::middleware('permission:manage administration,admin')->group(function () {
            Route::post('/general', [\App\Http\Controllers\Admin\System\GeneralSettingController::class, 'update'])->name('general.update');
            Route::post('/payment-gateways', [\App\Http\Controllers\Admin\System\GeneralSettingController::class, 'updatePaymentGateways'])->name('payment-gateways.update');
            Route::post('/auth', [\App\Http\Controllers\Admin\System\GeneralSettingController::class, 'updateAuthSettings'])->name('auth.update');
        });
    });

    Route::middleware('permission:view administration|manage administration,admin')->group(function () {
        Route::get('side-section', [\App\Http\Controllers\Admin\System\SideSectionController::class, 'index'])->name('side-section.index');
    });
    Route::middleware('permission:manage administration,admin')->group(function () {
        Route::post('side-section', [\App\Http\Controllers\Admin\System\SideSectionController::class, 'update'])->name('side-section.update');
    });
});

// ── Assessment / Quiz Management ─────────────────────────────────────────────
Route::prefix('admin/assessment')->name('admin.assessment.')->middleware('auth:admin')->group(function () {
    Route::middleware('permission:manage assessment questions,admin')->get('questions/create', [\App\Http\Controllers\Admin\Assessment\AssessmentQuestionController::class, 'create'])->name('questions.create');
    Route::middleware('permission:view assessment|manage assessment,admin')->group(function () {
        Route::resource('questions', \App\Http\Controllers\Admin\Assessment\AssessmentQuestionController::class)->only(['index', 'show']);
    });
    
    Route::middleware('permission:manage assessment,admin')->group(function () {
        Route::resource('questions', \App\Http\Controllers\Admin\Assessment\AssessmentQuestionController::class)->except(['index', 'show']);
        Route::patch('questions/{question}/toggle', [\App\Http\Controllers\Admin\Assessment\AssessmentQuestionController::class, 'toggleActive'])->name('questions.toggle');
        Route::post('questions/reorder', [\App\Http\Controllers\Admin\Assessment\AssessmentQuestionController::class, 'reorder'])->name('questions.reorder');
        Route::patch('sections/rename', [\App\Http\Controllers\Admin\Assessment\AssessmentSectionController::class, 'rename'])->name('sections.rename');
    });
});

// ── Pediatric Nutrition Management (Diet Chart) ────────────────────────────────
Route::prefix('admin/nutrition')->name('admin.nutrition.')->middleware('auth:admin')->group(function () {
    Route::middleware('permission:manage diet chart,admin')->group(function () {
        Route::get('questions/create', [\App\Http\Controllers\Admin\Nutrition\QuestionnaireAdminController::class, 'create'])->name('questions.create');
        Route::get('diet-subscriptions/create', [\App\Http\Controllers\Admin\Nutrition\DietSubscriptionController::class, 'create'])->name('diet-subscriptions.create');
    });
    Route::middleware('permission:view diet chart|manage diet chart,admin')->group(function () {
        Route::resource('questions', \App\Http\Controllers\Admin\Nutrition\QuestionnaireAdminController::class)->only(['index', 'show']);
        Route::get('diet-subscriptions/data', [\App\Http\Controllers\Admin\Nutrition\DietSubscriptionController::class, 'data'])->name('diet-subscriptions.data');
        Route::resource('diet-subscriptions', \App\Http\Controllers\Admin\Nutrition\DietSubscriptionController::class)->only(['index', 'show']);
        Route::get('foods/data', [\App\Http\Controllers\Admin\Nutrition\FoodController::class, 'data'])->name('foods.data');
        Route::get('foods', [\App\Http\Controllers\Admin\Nutrition\FoodController::class, 'index'])->name('foods.index');
        Route::get('foods/{id}', [\App\Http\Controllers\Admin\Nutrition\FoodController::class, 'show'])->whereNumber('id')->name('foods.show');
        Route::get('diet-rules', [\App\Http\Controllers\Admin\Nutrition\DietRuleController::class, 'index'])->name('diet-rules.index');
    });
    
    Route::middleware('permission:manage diet chart,admin')->group(function () {
        Route::resource('questions', \App\Http\Controllers\Admin\Nutrition\QuestionnaireAdminController::class)->except(['index', 'show', 'destroy']);
        Route::resource('diet-subscriptions', \App\Http\Controllers\Admin\Nutrition\DietSubscriptionController::class)->except(['index', 'show']);
        Route::get('leads', [\App\Http\Controllers\Admin\Nutrition\NutritionLeadController::class, 'index'])->name('leads.index');
        Route::get('leads/data', [\App\Http\Controllers\Admin\Nutrition\NutritionLeadController::class, 'data'])->name('leads.data');
        Route::delete('foods/{food}', [\App\Http\Controllers\Admin\Nutrition\FoodController::class, 'destroy'])->name('foods.destroy');
        Route::post('foods/bulk-upload', [\App\Http\Controllers\Admin\Nutrition\FoodController::class, 'bulkUploadStore'])->name('foods.bulk-upload.store'); // ← NEW
        Route::patch('leads/{lead}/assign', [\App\Http\Controllers\Admin\Nutrition\NutritionLeadController::class, 'assign'])->name('leads.assign');
        Route::patch('leads/bulk-assign', [\App\Http\Controllers\Admin\Nutrition\NutritionLeadController::class, 'bulkAssign'])->name('leads.bulk-assign');

        Route::get('my-leads', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'index'])->name('lead-queue.index');
        Route::get('my-leads/data', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'data'])->name('lead-queue.data');
        Route::get('my-leads/{lead}', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'show'])->name('lead-queue.show');
        Route::post('my-leads/{lead}/generate', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'generate'])->name('lead-queue.generate');
        Route::patch('my-leads/{lead}/diet-plans/{dietPlan}', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'update'])->name('lead-queue.update');
        Route::patch('my-leads/{lead}/diet-plans/{dietPlan}/products', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'updateProducts'])->name('lead-queue.products');
        Route::patch('my-leads/{lead}/diet-plans/{dietPlan}/approve', [\App\Http\Controllers\Admin\Nutrition\NutritionistQueueController::class, 'approve'])->name('lead-queue.approve');
    });
});
