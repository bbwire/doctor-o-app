<?php

use App\Http\Controllers\Api\Admin\ConsultationController as AdminConsultationController;
use App\Http\Controllers\Api\Admin\HealthcareProfessionalController as AdminHealthcareProfessionalController;
use App\Http\Controllers\Api\Admin\InstitutionController as AdminInstitutionController;
use App\Http\Controllers\Api\Admin\PrescriptionController as AdminPrescriptionController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationMessageController;
use App\Http\Controllers\Api\ConsultationWebrtcSignalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Patient\ConsultationController as PatientConsultationController;
use App\Http\Controllers\Api\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\Api\Patient\DoctorController as PatientDoctorController;
use App\Http\Controllers\Api\Patient\PrescriptionController as PatientPrescriptionController;
use App\Http\Controllers\Api\Patient\DependantController as PatientDependantController;
use App\Http\Controllers\Api\Patient\ConsultationMediaController as PatientConsultationMediaController;
use App\Http\Controllers\Api\Patient\WalletController as PatientWalletController;
use App\Http\Controllers\Api\Doctor\AcademicDocumentController as DoctorAcademicDocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'service' => 'doctor-o-api',
        'timestamp' => now()->toISOString(),
    ]));
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::patch('/user', [AuthController::class, 'updateProfile']);

        // Notifications (for any authenticated user: patient or admin)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/notifications/{notification}', [NotificationController::class, 'markRead']);

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
        });

        // Admin routes
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::apiResource('institutions', AdminInstitutionController::class);
            Route::apiResource('healthcare-professionals', AdminHealthcareProfessionalController::class);
            Route::apiResource('consultations', AdminConsultationController::class);
            Route::apiResource('prescriptions', AdminPrescriptionController::class);
        });
    });
});
