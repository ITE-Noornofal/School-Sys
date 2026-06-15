<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StudentController;

use App\Http\Controllers\API\Auth\TeacherController;
use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\ClassRoomController;
use \App\Http\Controllers\API\Auth\AdminAuthController;
use App\Http\Controllers\API\AdminStudentController;
use App\Http\Controllers\API\ClassTransferController;

use App\Http\Controllers\APi\AdminSpecializController;
use App\Http\Controllers\GradeSupervisorController;
 use App\Http\Controllers\GradeManagementController;
 use App\Http\Controllers\API\Auth\SupervisorAuthController;
 use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\API\Auth\AdminAccountantController;
use App\Http\Controllers\API\Auth\AccountantAuthController;
use App\Http\Controllers\API\Auth\AccountantPaymentController;
use App\Http\Controllers\API\Auth\GuardianController;
use App\Http\Controllers\API\Auth\AdminGuardianController;

use App\Http\Controllers\API\Auth\ComplaintController;

use App\Http\Controllers\SchoolTripController;
use Illuminate\Support\Facades\Auth;
use App\Models\Complaint;



use Illuminate\Http\Request;

Route::prefix('student')->group(function () {
    Route::post('/register', [StudentController::class, 'registerByEmail']);
    Route::post('/login', [StudentController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
        Route::post('/logout', [StudentController::class, 'logout']);
        Route::post('/setpassword',[StudentController::class,'setpassword']);
        Route::post('/Edit-Profile', [StudentController::class, 'saveprofile']);
       Route::get('/profile', [StudentController::class, 'profile']);
       Route::put('/update-profile', [StudentController::class, 'updateProfile']);
       Route::get('/trips', [StudentController::class, 'availableTrips']);

       //complaint
       Route::post('/complaints', [StudentController::class, 'storeByStudent']);
       Route::get('/complaints', [StudentController::class, 'index']);


    });
});


Route::prefix('teacher')->group(function () {

    // المسارات المفتوحة (register, login)
    Route::post('/register', [TeacherController::class, 'register']);
    Route::post('/login', [TeacherController::class, 'login']);

    // المسارات المحمية بمصادقة المعلم
    Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {

        Route::post('/logout', [TeacherController::class, 'logout']);
        Route::get('/profile', [TeacherController::class, 'profile']);
        Route::put('/updateprofile',[TeacherController::class,'updateProfile']);

        // مسارات إدارة العلامات مع صلاحية إضافية
        Route::middleware('permission:manage grades')->group(function () {
            Route::get('/grades', [GradeController::class, 'index']);
            Route::post('/grades', [GradeController::class, 'store']);
            Route::put('/grades/{id}', [GradeController::class, 'update']);
            Route::delete('/grades/{id}', [GradeController::class, 'destroy']);
        });
    });

});

Route::prefix('admin')->group(function () {

    // 1. 🔓 مسارات غير محمية (تسجيل وتسجيل دخول المدير)
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);

    // 2. 🔐 مسارات محمية - فقط المدير مصرح له
    Route::middleware(['auth:sanctum', 'guard.admin', 'role:admin'])->group(function () {

        // 2.1 عمليات مدير الحساب
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/profile', [AdminAuthController::class, 'profile']);

        // 2.2 🧑‍🎓 إدارة الطلاب (صلاحية: add students)
        Route::middleware(['permission:add students'])->group(function () {
            Route::post('/students', [AdminStudentController::class, 'store']);
            Route::post('/editstudentinfo/{id}', [AdminStudentController::class, 'update']);
        });

        // 2.3 👨‍🏫 إضافة المعلمين (صلاحية: addteacher)
        Route::middleware(['permission:addteacher'])->group(function () {
            Route::post('/add-teacher', [AdminStudentController::class, 'addTeacher']);
        });

        // 2.4 🏫 إدارة الشعب (صلاحية: editclasses)
        Route::middleware(['permission:editclasses'])->group(function () {
            Route::apiResource('classes', ClassRoomController::class);
        });

        // 2.5 🔄 نقل الطلاب بين الصفوف (صلاحية: transfer students)
        Route::middleware(['permission:transfer students'])->group(function () {
            Route::post('/students/transfer', [ClassTransferController::class, 'store']);
             Route::get('/students/{id}/transfers', [ClassTransferController::class, 'history']);
        });

        // 2.6 إدارة الاختصاصات (صلاحية: editclasses)
        Route::middleware(['permission:editclasses'])->group(function () {
            Route::apiResource('/specializations', AdminSpecializController::class);
        });

        // 2.7 إدارة المشرفين
        Route::post('/supervisors/add-email', [AdminAuthController::class, 'addEmail']);
        Route::put('/supervisors/{id}', [AdminAuthController::class, 'updateSupervisor']);
        Route::delete('/supervisors/delete-email', [AdminAuthController::class, 'deleteByEmail']);

        // تعيين، نقل، وإلغاء تعيين المشرفين
        Route::post('/supervisors/assign', [GradeSupervisorController::class, 'assign']);
        Route::post('/supervisors/move', [GradeSupervisorController::class, 'move']);
        Route::post('/supervisors/unassign', [GradeSupervisorController::class, 'unassign']);

        // 2.8 إدارة الصفوف
        Route::get('/Get/grades', [GradeManagementController::class, 'index']);
        Route::get('/grades/{id}', [GradeManagementController::class, 'show']);
        Route::post('/grades', [GradeManagementController::class, 'store']);
        Route::put('/grades/{id}', [GradeManagementController::class, 'update']);
        Route::delete('/grades/{id}', [GradeManagementController::class, 'destroy']);

        // 2.9 🧾 إدارة المحاسبين (داخل prefix فرعي مع حماية مدير)
        Route::prefix('accountants')->group(function () {
            Route::post('/add-email', [AdminAccountantController::class, 'addEmail']);       // إضافة إيميل محاسب
            Route::put('/{accountant}', [AdminAccountantController::class, 'update']);      // تعديل معلومات محاسب
            Route::delete('/{accountant}', [AdminAccountantController::class, 'destroy']);  // حذف محاسب
            Route::get('/', [AdminAccountantController::class, 'index']);                   // عرض قائمة المحاسبين (اختياري)

        });

        // 2.10 👨‍👧‍👦 إدارة أولياء الأمور (داخل prefix فرعي مع حماية مدير)
    Route::prefix('guardians')->group(function () {
    Route::post('/add-email', [AdminGuardianController::class, 'addEmail']);       // إضافة إيميل ولي أمر
    Route::put('/{guardian}', [AdminGuardianController::class, 'update']);         // تعديل معلومات ولي أمر
    Route::delete('/{guardian}', [AdminGuardianController::class, 'destroy']);     // حذف ولي أمر
    Route::get('/', [AdminGuardianController::class, 'index']);                    // عرض قائمة أولياء الأمور (اختياري)
    Route::post('/assign-student-to-guardian', [AdminGuardianController::class, 'assignStudent']);
    Route::get('/unassigned-students', [AdminGuardianController::class, 'unassignedStudents']);//لعرض جميع الطلاب غير المرتبطين بأي ولي أمر
    Route::post('/{guardian}/recharge', [AdminGuardianController::class, 'rechargeBalance']);
});

//عرض الشكاوي

Route::get('/complaints', [ComplaintController::class, 'index']);
//عرض  طلاب  الباص
Route::get('/bus/{id}/students', [SchoolTripController::class, 'studentsByBus']);
Route::post('/buses', [AdminAuthController::class, 'AddBus']);
    });
});
       Route::prefix('accountants')->group(function () {
    // مسارات التسجيل والدخول (غير محمية)
    Route::post('/register', [AccountantAuthController::class, 'register']);
    Route::post('/login', [AccountantAuthController::class, 'login']);

    // مسارات محمية (تحتاج تسجيل دخول وصلاحية "accountant")
    Route::middleware(['auth:accountant', 'role:accountant'])->group(function () {
        Route::post('/logout', [AccountantAuthController::class, 'logout']);
        Route::post('/setpassword', [AccountantAuthController::class, 'setpassword']);  // لو عندك
        Route::post('/edit-profile', [AccountantAuthController::class, 'updateOrCreateProfile']);  // لو عندك
        Route::get('/profile', [AccountantAuthController::class, 'profile']);

        Route::get('/Getpayments', [AccountantPaymentController::class, 'index']);   // عرض الدفعات
        Route::post('/Addpayments', [AccountantPaymentController::class, 'store']);  // إضافة دفعة
        Route::get('/class-rooms/{id}/due-payment-templates', [AccountantPaymentController::class, 'byClassRoom']);

        //[جلب  التقرير الشهري  ]
    Route::get('/report/monthly-summary', [AccountantPaymentController::class, 'monthlySummary']);

   Route::get('/report/guardian-summary', [AccountantPaymentController::class, 'guardianPaymentSummary']);
   Route::post('/due-payments/update-penalties', [AccountantPaymentController::class, 'updatePenalties']);


    });
});


Route::prefix('guardians')->group(function () {

    // ✅ التسجيل وتسجيل الدخول
    Route::post('/register', [GuardianController::class, 'register']);
    Route::post('/login', [GuardianController::class, 'login']);

    // ✅ المسارات التي تتطلب تسجيل دخول Guardian
    Route::middleware(['auth:guardian'])->group(function () {

        // 📄 الملف الشخصي
        Route::get('/profile', [GuardianController::class, 'profile']);
        Route::put('/profile', [GuardianController::class, 'updateProfile']);

        // 👨‍👩‍👧 الأبناء ولوحة التحكم
        Route::get('/children', [GuardianController::class, 'children']);
        Route::get('/dashboard', [GuardianController::class, 'dashboard']);

        // 💳 الدفع والدفعات
        Route::post('/payments/{id}/pay', [GuardianController::class, 'pay']);
        Route::get('/due-payments/unpaid', [GuardianController::class, 'unpaidDuePayments']);

       //سجل  نقل  ابنائه
       Route::get('/students/transfers', [ClassTransferController::class, 'guardianTransferHistory']);


        // 📝 الشكاوى
        Route::post('/complaints', [ComplaintController::class, 'storeByGuardian']);     // إنشاء شكوى
        Route::get('/complaints', [ComplaintController::class, 'index']);      // عرض الشكاوى الخاصة به
         //
           Route::get('/trips', [GuardianController::class, 'guardianTrips']);
         Route::post('/school-trips/{tripId}/confirm', [GuardianController::class, 'payAndConfirmAttendance']);

    });
});

        Route::prefix('supervisors')->group(function () {
            Route::post('/register', [SupervisorAuthController::class, 'register']);
            Route::post('/login', [SupervisorAuthController::class, 'login']);

            Route::middleware(['auth:supervisor', 'role:supervisor'])->group(function () {
                Route::post('/logout', [SupervisorAuthController::class, 'logout']);
                Route::post('/setpassword',[SupervisorAuthController::class,'setpassword']);
                Route::post('/Edit-Profile', [SupervisorAuthController::class, 'saveprofile']);
            Route::get('/profile', [SupervisorAuthController::class, 'profile']);
            Route::put('/update-profile', [SupervisorAuthController::class, 'updateProfile']);
            Route::get('/school-trips/{id}/confirmed-students', [SupervisorAuthController::class, 'confirmedStudents']);
              // سجل  نقل  الطلاب
            Route::get('/students/{id}/transfers', [SupervisorAuthController::class, 'history']);
            });

            Route::middleware(['auth:supervisor','permission:take attendance'])->group(function () {
                    Route::post('/attendance/take', [AttendanceController::class, 'takeAttendance']);
                    Route::delete('/attendance/cancel', [AttendanceController::class, 'cancelAttendance']);
                    Route::get('/attendance/view',[AttendanceController::class,'viewReport']);

                    Route::post('/trips', [SchoolTripController::class, 'store']);
                    Route::get('/school-trips', [SchoolTripController::class, 'index']);
                });

 });
