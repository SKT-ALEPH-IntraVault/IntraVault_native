<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,DocumentController,AdminController,LabController};
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'create'])->name('login');
    Route::post('/login',[AuthController::class,'store'])->name('login.store');
});
Route::middleware('lab.auth')->group(function () {
    Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
    Route::get('/',function () {
        $user=auth()->user();
        return view('dashboard',['documentCount'=>App\Models\Document::visibleTo($user)->count(),
            'favoriteCount'=>$user->favorites()->visibleTo($user)->count(),
            'recent'=>App\Models\Document::with('department')->visibleTo($user)->latest()->limit(5)->get()]);
    })->name('dashboard');
    Route::get('/favorites',[DocumentController::class,'index'])->name('favorites');
    Route::get('/my-access-logs',[AdminController::class,'logs'])->name('my.logs');
    Route::get('/department/manage',[AdminController::class,'department'])->name('department.manage');
    Route::resource('documents',DocumentController::class);
    Route::get('/documents/{document}/download',[DocumentController::class,'download'])->name('documents.download');
    Route::post('/documents/{document}/favorite',[DocumentController::class,'favorite'])->name('documents.favorite');
    Route::post('/documents/{document}/shares',[DocumentController::class,'share'])->name('documents.share');
    Route::delete('/documents/{document}/shares/{user}',[DocumentController::class,'unshare'])->name('documents.unshare');
    Route::get('/lab/search',[LabController::class,'workspace'])->name('lab.workspace');
    Route::get('/lab/files',[LabController::class,'path'])->name('lab.path');
    Route::post('/lab/uploads',[LabController::class,'upload'])->name('lab.upload');
    Route::get('/lab/uploads/{id}',[LabController::class,'downloadUpload'])->name('lab.upload.download');
    Route::prefix('admin')->name('admin.')->middleware('system_admin')->group(function () {
        Route::get('/',[AdminController::class,'dashboard'])->name('dashboard');
        Route::get('/users',[AdminController::class,'users'])->name('users');
        Route::get('/users/create',[AdminController::class,'createUser'])->name('users.create');
        Route::post('/users',[AdminController::class,'saveUser'])->name('users.store');
        Route::get('/users/{user}/edit',[AdminController::class,'editUser'])->name('users.edit');
        Route::put('/users/{user}',[AdminController::class,'saveUser'])->name('users.update');
        Route::get('/departments',[AdminController::class,'departments'])->name('departments');
        Route::post('/departments',[AdminController::class,'saveDepartment'])->name('departments.store');
        Route::put('/departments/{department}',[AdminController::class,'saveDepartment'])->name('departments.update');
        Route::delete('/departments/{department}',[AdminController::class,'deleteDepartment'])->name('departments.destroy');
        Route::get('/documents',[DocumentController::class,'index'])->name('documents');
        Route::get('/confidential-documents',function (\Illuminate\Http\Request $r) { $r->merge(['level'=>'confidential']); return app(DocumentController::class)->index($r); })->name('confidential');
        Route::get('/permissions',[AdminController::class,'permissions'])->name('permissions');
        Route::get('/audit-logs',[AdminController::class,'logs'])->name('logs');
        Route::get('/security-lab',[LabController::class,'index'])->name('lab');
        Route::post('/security-lab/preset',[LabController::class,'preset'])->name('lab.preset');
        Route::post('/security-lab/controls/{id}',[LabController::class,'control'])->name('lab.control');
        Route::post('/security-lab/{id}',[LabController::class,'toggle'])->name('lab.toggle');
        Route::get('/training-report',[AdminController::class,'trainingReport'])->name('training-report');
    });
});
