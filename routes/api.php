<?php

use Illuminate\Support\Facades\Route;

// ============================================================
// CONTROLLERS
// ============================================================

use App\Http\Controllers\API\StudentController;

use App\Http\Controllers\API\Auth\TeacherController;
use App\Http\Controllers\API\Auth\GuardianController;
use App\Http\Controllers\API\Auth\AdminAuthController;
use App\Http\Controllers\API\Auth\AdminGuardianController;
use App\Http\Controllers\API\Auth\AdminAccountantController;
use App\Http\Controllers\API\Auth\AccountantAuthController;
use App\Http\Controllers\API\Auth\AccountantPaymentController;
use App\Http\Controllers\API\Auth\SupervisorAuthController;
use App\Http\Controllers\API\Auth\ComplaintController;
use App\Http\Controllers\API\Auth\UnifiedAuthController;

use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\ClassRoomController;
use App\Http\Controllers\API\AdminStudentController;
use App\Http\Controllers\API\ClassTransferController;
use App\Http\Controllers\APi\AdminSpecializController;

use App\Http\Controllers\GradeSupervisorController;
use App\Http\Controllers\GradeManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SchoolTripController;
use App\Http\Controllers\API\PasswordResetController;


// ============================================================
// PASSWORD RESET
// ============================================================

Route::post('/forgot-password', [
    PasswordResetController::class,
    'sendCode'
]);

Route::post('/verify-code', [
    PasswordResetController::class,
    'verifyCode'
]);

Route::post('/reset-password', [
    PasswordResetController::class,
    'resetPassword'
]);


// ============================================================
// UNIFIED AUTH
// Student + Teacher + Guardian
// ============================================================

Route::prefix('auth')->group(function () {

    // ========================================================
    // REGISTER
    // EMAIL ONLY
    // ========================================================

    Route::post('/register', [
        UnifiedAuthController::class,
        'register'
    ]);


    // ========================================================
    // SET PASSWORD
    // Student + Teacher + Guardian
    //
    // مهم:
    // لا يوجد role middleware هنا
    // لأن المستخدم لديه Token من register
    // ========================================================

    Route::post('/set-password', [
        UnifiedAuthController::class,
        'setPassword'
    ])->middleware('auth:sanctum');


    // ========================================================
    // LOGIN
    // ========================================================

    Route::post('/login', [
        UnifiedAuthController::class,
        'login'
    ]);


    // ========================================================
    // ME
    // ========================================================

    Route::get('/me', [
        UnifiedAuthController::class,
        'me'
    ])->middleware('auth:sanctum');


    // ========================================================
    // LOGOUT
    // ========================================================

    Route::post('/logout', [
        UnifiedAuthController::class,
        'logout'
    ])->middleware('auth:sanctum');
});


// ============================================================
// STUDENT
// ============================================================

Route::prefix('student')
    ->middleware(['auth:sanctum', 'role:student'])
    ->group(function () {

        Route::post('/logout', [
            UnifiedAuthController::class,
            'logout'
        ]);

        Route::get('/profile', [
            StudentController::class,
            'profile'
        ]);

        Route::post('/Edit-Profile', [
            StudentController::class,
            'saveProfile'
        ]);

        Route::put('/update-profile', [
            StudentController::class,
            'saveProfile'
        ]);

        Route::get('/trips', [
            StudentController::class,
            'availableTrips'
        ]);

        Route::post('/complaints', [
            StudentController::class,
            'storeByStudent'
        ]);

        Route::get('/complaints', [
            StudentController::class,
            'index'
        ]);
    });


// ============================================================
// TEACHER
// ============================================================

Route::prefix('teacher')
    ->middleware(['auth:sanctum', 'role:teacher'])
    ->group(function () {

        Route::post('/logout', [
            UnifiedAuthController::class,
            'logout'
        ]);

        Route::get('/profile', [
            TeacherController::class,
            'profile'
        ]);

        Route::put('/updateprofile', [
            TeacherController::class,
            'updateProfile'
        ]);

        // Grades
        Route::get('/grades', [
            GradeController::class,
            'index'
        ]);

        Route::post('/grades', [
            GradeController::class,
            'store'
        ]);

        Route::put('/grades/{id}', [
            GradeController::class,
            'update'
        ]);

        Route::delete('/grades/{id}', [
            GradeController::class,
            'destroy'
        ]);
    });


// ============================================================
// GUARDIAN
// ============================================================

Route::prefix('guardians')
    ->middleware(['auth:sanctum', 'role:guardian'])
    ->group(function () {

        Route::post('/logout', [
            UnifiedAuthController::class,
            'logout'
        ]);

        Route::get('/profile', [
            GuardianController::class,
            'profile'
        ]);

        Route::put('/profile', [
            GuardianController::class,
            'updateProfile'
        ]);

        Route::get('/children', [
            GuardianController::class,
            'children'
        ]);

        Route::get('/dashboard', [
            GuardianController::class,
            'dashboard'
        ]);

        Route::post('/payments/{id}/pay', [
            GuardianController::class,
            'pay'
        ]);

        Route::get('/due-payments/unpaid', [
            GuardianController::class,
            'unpaidDuePayments'
        ]);

        Route::get('/students/transfers', [
            ClassTransferController::class,
            'guardianTransferHistory'
        ]);

        Route::post('/complaints', [
            ComplaintController::class,
            'storeByGuardian'
        ]);

        Route::get('/complaints', [
            ComplaintController::class,
            'index'
        ]);

        Route::get('/trips', [
            GuardianController::class,
            'guardianTrips'
        ]);

        Route::post('/school-trips/{tripId}/confirm', [
            GuardianController::class,
            'payAndConfirmAttendance'
        ]);
    });


// ============================================================
// ADMIN
// ============================================================

Route::prefix('admin')->group(function () {

    Route::post('/register', [
        AdminAuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AdminAuthController::class,
        'login'
    ]);

    Route::middleware([
        'auth:sanctum',
        'guard.admin',
        'role:admin'
    ])->group(function () {

        Route::post('/logout', [
            AdminAuthController::class,
            'logout'
        ]);

        Route::get('/profile', [
            AdminAuthController::class,
            'profile'
        ]);


        // ====================================================
        // STUDENTS
        // ====================================================

        Route::middleware('permission:add students')->group(function () {

            Route::post('/students', [
                AdminStudentController::class,
                'store'
            ]);

            Route::post('/editstudentinfo/{id}', [
                AdminStudentController::class,
                'update'
            ]);
        });


        // ====================================================
        // TEACHERS
        // ====================================================

        Route::middleware('permission:addteacher')->group(function () {

            Route::post('/add-teacher', [
                AdminStudentController::class,
                'addTeacher'
            ]);
        });


        // ====================================================
        // CLASSES
        // ====================================================

        Route::middleware('permission:editclasses')->group(function () {

            Route::apiResource(
                'classes',
                ClassRoomController::class
            );
        });


        // ====================================================
        // TRANSFER STUDENTS
        // ====================================================

        Route::middleware('permission:transfer students')->group(function () {

            Route::post('/students/transfer', [
                ClassTransferController::class,
                'store'
            ]);

            Route::get('/students/{id}/transfers', [
                ClassTransferController::class,
                'history'
            ]);
        });


        // ====================================================
        // SPECIALIZATIONS
        // ====================================================

        Route::middleware('permission:editclasses')->group(function () {

            Route::apiResource(
                '/specializations',
                AdminSpecializController::class
            );
        });


        // ====================================================
        // SUPERVISORS MANAGEMENT
        // ====================================================

        Route::post('/supervisors/add-email', [
            AdminAuthController::class,
            'addEmail'
        ]);

        Route::put('/supervisors/{id}', [
            AdminAuthController::class,
            'updateSupervisor'
        ]);

        Route::delete('/supervisors/delete-email', [
            AdminAuthController::class,
            'deleteByEmail'
        ]);

        Route::post('/supervisors/assign', [
            GradeSupervisorController::class,
            'assign'
        ]);

        Route::post('/supervisors/move', [
            GradeSupervisorController::class,
            'move'
        ]);

        Route::post('/supervisors/unassign', [
            GradeSupervisorController::class,
            'unassign'
        ]);


        // ====================================================
        // GRADES
        // ====================================================

        Route::get('/Get/grades', [
            GradeManagementController::class,
            'index'
        ]);

        Route::get('/grades/{id}', [
            GradeManagementController::class,
            'show'
        ]);

        Route::post('/grades', [
            GradeManagementController::class,
            'store'
        ]);

        Route::put('/grades/{id}', [
            GradeManagementController::class,
            'update'
        ]);

        Route::delete('/grades/{id}', [
            GradeManagementController::class,
            'destroy'
        ]);


        // ====================================================
        // ACCOUNTANTS MANAGEMENT
        // ====================================================

        Route::prefix('accountants')->group(function () {

            Route::post('/add-email', [
                AdminAccountantController::class,
                'addEmail'
            ]);

            Route::put('/{accountant}', [
                AdminAccountantController::class,
                'update'
            ]);

            Route::delete('/{accountant}', [
                AdminAccountantController::class,
                'destroy'
            ]);

            Route::get('/', [
                AdminAccountantController::class,
                'index'
            ]);
        });


        // ====================================================
        // GUARDIANS MANAGEMENT
        // ====================================================

        Route::prefix('guardians')->group(function () {

            Route::post('/add-email', [
                AdminGuardianController::class,
                'addEmail'
            ]);

            Route::put('/{guardian}', [
                AdminGuardianController::class,
                'update'
            ]);

            Route::delete('/{guardian}', [
                AdminGuardianController::class,
                'destroy'
            ]);

            Route::get('/', [
                AdminGuardianController::class,
                'index'
            ]);

            Route::post('/assign-student-to-guardian', [
                AdminGuardianController::class,
                'assignStudent'
            ]);

            Route::get('/unassigned-students', [
                AdminGuardianController::class,
                'unassignedStudents'
            ]);

            Route::post('/{guardian}/recharge', [
                AdminGuardianController::class,
                'rechargeBalance'
            ]);
        });


        // ====================================================
        // COMPLAINTS
        // ====================================================

        Route::get('/complaints', [
            ComplaintController::class,
            'index'
        ]);


        // ====================================================
        // BUS
        // ====================================================

        Route::get('/bus/{id}/students', [
            SchoolTripController::class,
            'studentsByBus'
        ]);

        Route::post('/buses', [
            AdminAuthController::class,
            'AddBus'
        ]);
    });
});


// ============================================================
// ACCOUNTANT
// النظام القديم
// ============================================================

Route::prefix('accountants')->group(function () {

    Route::post('/register', [
        AccountantAuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AccountantAuthController::class,
        'login'
    ]);

    Route::middleware([
        'auth:accountant',
        'role:accountant'
    ])->group(function () {

        Route::post('/logout', [
            AccountantAuthController::class,
            'logout'
        ]);

        Route::post('/setpassword', [
            AccountantAuthController::class,
            'setpassword'
        ]);

        Route::post('/edit-profile', [
            AccountantAuthController::class,
            'updateOrCreateProfile'
        ]);

        Route::get('/profile', [
            AccountantAuthController::class,
            'profile'
        ]);

        Route::get('/Getpayments', [
            AccountantPaymentController::class,
            'index'
        ]);

        Route::post('/Addpayments', [
            AccountantPaymentController::class,
            'store'
        ]);

        Route::get(
            '/class-rooms/{id}/due-payment-templates',
            [
                AccountantPaymentController::class,
                'byClassRoom'
            ]
        );

        Route::get(
            '/report/monthly-summary',
            [
                AccountantPaymentController::class,
                'monthlySummary'
            ]
        );

        Route::get(
            '/report/guardian-summary',
            [
                AccountantPaymentController::class,
                'guardianPaymentSummary'
            ]
        );

        Route::post(
            '/due-payments/update-penalties',
            [
                AccountantPaymentController::class,
                'updatePenalties'
            ]
        );
    });
});


// ============================================================
// SUPERVISOR
// النظام القديم
// ============================================================

Route::prefix('supervisors')->group(function () {

    Route::post('/register', [
        SupervisorAuthController::class,
        'register'
    ]);

    Route::post('/login', [
        SupervisorAuthController::class,
        'login'
    ]);

    Route::middleware([
        'auth:supervisor',
        'role:supervisor'
    ])->group(function () {

        Route::post('/logout', [
            SupervisorAuthController::class,
            'logout'
        ]);

        Route::post('/setpassword', [
            SupervisorAuthController::class,
            'setpassword'
        ]);

        Route::post('/Edit-Profile', [
            SupervisorAuthController::class,
            'saveprofile'
        ]);

        Route::get('/profile', [
            SupervisorAuthController::class,
            'profile'
        ]);

        Route::put('/update-profile', [
            SupervisorAuthController::class,
            'updateProfile'
        ]);

        Route::get(
            '/school-trips/{id}/confirmed-students',
            [
                SupervisorAuthController::class,
                'confirmedStudents'
            ]
        );

        Route::get(
            '/students/{id}/transfers',
            [
                SupervisorAuthController::class,
                'history'
            ]
        );
    });


    Route::middleware([
        'auth:supervisor',
        'permission:take attendance'
    ])->group(function () {

        Route::post(
            '/attendance/take',
            [
                AttendanceController::class,
                'takeAttendance'
            ]
        );

        Route::delete(
            '/attendance/cancel',
            [
                AttendanceController::class,
                'cancelAttendance'
            ]
        );

        Route::get(
            '/attendance/view',
            [
                AttendanceController::class,
                'viewReport'
            ]
        );

        Route::post(
            '/trips',
            [
                SchoolTripController::class,
                'store'
            ]
        );

        Route::get(
            '/school-trips',
            [
                SchoolTripController::class,
                'index'
            ]
        );
    });
});
