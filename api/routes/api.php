<?php

use App\Http\Controllers\Api\Admin\ConsultationController as AdminConsultationController;
use App\Http\Controllers\Api\Admin\HealthcareProfessionalController as AdminHealthcareProfessionalController;
use App\Http\Controllers\Api\Admin\InstitutionController as AdminInstitutionController;
use App\Http\Controllers\Api\Admin\InstitutionDocumentController;
use App\Http\Controllers\Api\Admin\PrescriptionController as AdminPrescriptionController;
use App\Http\Controllers\Api\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\CacheController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationMessageController;
use App\Http\Controllers\Api\ConsultationRecordingController;
use App\Http\Controllers\Api\ConsultationWebrtcSignalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Patient\ConsultationController as PatientConsultationController;
use App\Http\Controllers\Api\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\Api\Patient\DoctorController as PatientDoctorController;
use App\Http\Controllers\Api\Patient\PrescriptionController as PatientPrescriptionController;
use App\Http\Controllers\Api\Patient\DependantController as PatientDependantController;
use App\Http\Controllers\Api\Patient\ConsultationMediaController as PatientConsultationMediaController;
use App\Http\Controllers\Api\Patient\WalletController as PatientWalletController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\Doctor\AcademicDocumentController as DoctorAcademicDocumentController;
use App\Http\Controllers\Api\JitsiController;
use App\Http\Controllers\Api\InstitutionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'service' => 'doctor-o-api',
        'timestamp' => now()->toISOString(),
    ]));
    Route::get('/institutions', [InstitutionController::class, 'index']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/artisan/migrate', function () {
        try {
          Artisan::call('migrate', ['--force' => true]);
          $output = Artisan::output();
          return response()->json([
            'success' => true,
            'message' => 'Migration completed',
            'output' => trim($output),
          ]);
        } catch (\Throwable $e) {
          return response()->json([
            'success' => false,
            'message' => 'Migration failed',
            'error' => $e->getMessage(),
          ], 500);
        }
      });

    Route::get('/artisan/seed', function () {
        try {
          Artisan::call('db:seed', ['--force' => true]);
          $output = Artisan::output();
          return response()->json([
            'success' => true,
            'message' => 'Database seeded',
            'output' => trim($output),
          ]);
        } catch (\Throwable $e) {
          return response()->json([
            'success' => false,
            'message' => 'Seeding failed',
            'error' => $e->getMessage(),
          ], 500);
        }
      });

    Route::get('/cache/clear', [CacheController::class, 'clear']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::patch('/user', [AuthController::class, 'updateProfile']);

        // Notifications (for any authenticated user: patient or admin)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/notifications/{notification}', [NotificationController::class, 'markRead']);

        // Shared/authenticated routes (any role)
        
        // Jitsi video conferencing routes
        Route::get('/jitsi/config', [JitsiController::class, 'getConfig']);
        Route::post('/jitsi/generate-token', [JitsiController::class, 'generateToken']);

        Route::middleware('patient')->group(function () {
            Route::get('/dashboard/summary', [PatientDashboardController::class, 'summary']);
            Route::get('/doctors', [PatientDoctorController::class, 'index']);
            Route::get('/doctors/{doctorId}/availability', [PatientDoctorController::class, 'availability']);
            Route::get('/consultations', [PatientConsultationController::class, 'index']);
            Route::post('/consultations/book', [PatientConsultationController::class, 'store']);
            Route::get('/consultations/{consultationId}', [PatientConsultationController::class, 'show']);
            Route::get('/consultations/{consultation}/messages', [ConsultationMessageController::class, 'index']);
            Route::post('/consultations/{consultation}/messages', [ConsultationMessageController::class, 'store']);
            Route::get('/consultations/{consultation}/webrtc-signals', [ConsultationWebrtcSignalController::class, 'index']);
            Route::post('/consultations/{consultation}/webrtc-signals', [ConsultationWebrtcSignalController::class, 'store']);
            Route::patch('/consultations/{consultationId}/cancel', [PatientConsultationController::class, 'cancel']);
            Route::patch('/consultations/{consultationId}/reschedule', [PatientConsultationController::class, 'reschedule']);
            Route::get('/prescriptions', [PatientPrescriptionController::class, 'index']);
            Route::get('/dependants', [PatientDependantController::class, 'index']);
            Route::post('/dependants', [PatientDependantController::class, 'store']);
            Route::delete('/dependants/{dependant}', [PatientDependantController::class, 'destroy']);
            Route::post('/consultations/reason-images', [PatientConsultationMediaController::class, 'storeReasonImage']);
            Route::get('/wallet', [PatientWalletController::class, 'show']);
            Route::post('/wallet/top-up', [PatientWalletController::class, 'topUp']);
            Route::post('/wallet/top-up/initiate', [PatientWalletController::class, 'initiateTopUp']);
            Route::get('/wallet/top-up/{topUp}', [PatientWalletController::class, 'showTopUp']);
            Route::post('/consultations/{consultation}/recording', [ConsultationRecordingController::class, 'store']);
        });

        // Doctor routes
        Route::prefix('doctor')->middleware([\App\Http\Middleware\EnsureUserIsDoctor::class])->group(function () {
            Route::get('/dashboard/summary', [\App\Http\Controllers\Api\Doctor\DashboardController::class, 'summary']);
            Route::get('/consultations', [\App\Http\Controllers\Api\Doctor\ConsultationController::class, 'index']);
            Route::get('/consultations/{consultation}', [\App\Http\Controllers\Api\Doctor\ConsultationController::class, 'show']);
            Route::patch('/consultations/{consultation}', [\App\Http\Controllers\Api\Doctor\ConsultationController::class, 'update']);
            Route::get('/consultations/{consultation}/messages', [ConsultationMessageController::class, 'index']);
            Route::post('/consultations/{consultation}/messages', [ConsultationMessageController::class, 'store']);
            Route::get('/consultations/{consultation}/webrtc-signals', [ConsultationWebrtcSignalController::class, 'index']);
            Route::post('/consultations/{consultation}/webrtc-signals', [ConsultationWebrtcSignalController::class, 'store']);
            Route::get('/prescriptions', [\App\Http\Controllers\Api\Doctor\PrescriptionController::class, 'index']);
            Route::post('/prescriptions', [\App\Http\Controllers\Api\Doctor\PrescriptionController::class, 'store']);
            Route::get('/academic-documents', [DoctorAcademicDocumentController::class, 'index']);
            Route::post('/academic-documents', [DoctorAcademicDocumentController::class, 'store']);
            Route::delete('/academic-documents/{academicDocument}', [DoctorAcademicDocumentController::class, 'destroy']);
            Route::post('/consultations/{consultation}/recording', [ConsultationRecordingController::class, 'store']);
            Route::get('/profile', [DoctorProfileController::class, 'show']);
            Route::patch('/profile', [DoctorProfileController::class, 'update']);
            Route::get('/wallet', [\App\Http\Controllers\Api\Doctor\WalletController::class, 'summary']);
            Route::get('/payout-requests', [\App\Http\Controllers\Api\Doctor\WalletController::class, 'payoutRequests']);
            Route::post('/payout-requests', [\App\Http\Controllers\Api\Doctor\WalletController::class, 'requestPayout']);
        });

        // Admin routes (super_admin has all permissions; admin has permission-based access)
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('permissions', [\App\Http\Controllers\Api\Admin\PermissionsController::class, 'index']);
            Route::middleware('admin_permission:manage_users')->group(function () {
                Route::post('users/{user}/top-up', [AdminUserController::class, 'topUp']);
                Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            });
            Route::middleware('admin_permission:manage_institutions')->group(function () {
                Route::apiResource('institutions', AdminInstitutionController::class);
                Route::post('institutions/{institution}/practicing-certificate', [InstitutionDocumentController::class, 'uploadPracticingCertificate']);
                Route::delete('institutions/{institution}/practicing-certificate', [InstitutionDocumentController::class, 'deletePracticingCertificate']);
            });
            Route::middleware('admin_permission:manage_healthcare_professionals')->group(function () {
                Route::patch('healthcare-professionals/{healthcare_professional}/status', [AdminHealthcareProfessionalController::class, 'updateStatus']);
                Route::apiResource('healthcare-professionals', AdminHealthcareProfessionalController::class);
            });
            Route::middleware('admin_permission:manage_consultations')->group(function () {
                Route::apiResource('consultations', AdminConsultationController::class);
            });
            Route::middleware('admin_permission:manage_prescriptions')->group(function () {
                Route::apiResource('prescriptions', AdminPrescriptionController::class);
            });
            Route::middleware('admin_permission:manage_settings')->group(function () {
                Route::get('settings/audit', [AdminSettingsController::class, 'audit']);
                Route::get('settings', [AdminSettingsController::class, 'index']);
                Route::patch('settings', [AdminSettingsController::class, 'update']);
            });
            Route::middleware('admin_permission:manage_finance')->group(function () {
                Route::get('finance', [\App\Http\Controllers\Api\Admin\FinanceController::class, 'index']);
                Route::get('finance/top-ups', [\App\Http\Controllers\Api\Admin\FinanceController::class, 'topUps']);
                Route::get('finance/settlements', [\App\Http\Controllers\Api\Admin\FinanceController::class, 'settlements']);
            });
        });
    });

    // Payment webhooks (public, secured via provider auth/signature and/or IP allowlist)
    Route::post('/payments/mtn-momo/webhook', [PaymentWebhookController::class, 'handleMtnMomo']);
    Route::post('/payments/airtel-money/webhook', [PaymentWebhookController::class, 'handleAirtel']);
});
