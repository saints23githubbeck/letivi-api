<?php


use App\Http\Controllers\AlbumController;
use App\Http\Controllers\BioController;
use App\Http\Controllers\BlockPostController;
use App\Http\Controllers\BlockUserController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CarerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClapController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\ComplainController;
use App\Http\Controllers\ComplainFlagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\Dummy\DummyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FollowingBusinessController;
use App\Http\Controllers\FollowingEventController;
use App\Http\Controllers\FollowingProjectController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ImpressionController;
use App\Http\Controllers\IndustryController;

use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LoveController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NewsLetterController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfessionalInfoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SharePostCountController;
use App\Http\Controllers\UserAuth\AppleController;
use App\Http\Controllers\UserAuth\AuthController;
use App\Http\Controllers\UserAuth\LoginController;
use App\Http\Controllers\UserAuth\OtpController;
use App\Http\Controllers\UserAuth\SignupController;
use App\Http\Controllers\UserFollowingController;
use App\Http\Controllers\UserProjectController;
use App\Http\Controllers\ViewController;
use App\Models\ComplainFlag;
use App\Models\FollowingBusiness;
use App\Models\FollowingEvent;
use App\Models\FollowingProject;
use App\Models\Media;
use App\Models\SharePostCount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use OpenApi\Annotations\Get;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Business page  route
    Route::controller(AlbumController::class)->group(function () {
        Route::post('/albums', 'create');
        Route::patch('/albums/{album}', 'update');
        Route::delete('/albums/{album}', 'destroy');
        Route::get('/user/albums', 'myAlbum');
        Route::get('/user/album/posts/{album}', 'myAlbumPost');
    });



    // Business page  route
    Route::controller(BusinessController::class)->group(function () {
        // Route::get('/business', 'index');
        Route::post('/business', 'create');
        Route::patch('/business/{business}', 'update');
        Route::delete('/business/{business}', 'destroy');
        Route::get('/user/my/business/workspaces', 'myBusinessPage');
    });


    // Event page  route
    Route::controller(EventController::class)->group(function () {
        Route::get('/events', 'index');
        Route::post('/events', 'create');
        Route::patch('/events/{event}', 'update');
        Route::delete('/events/{event}', 'destroy');
        Route::get('/user/my/events/workspaces', 'myEventPage');
    });

    // Event page  route
    Route::controller(ProjectController::class)->group(function () {
        //        Route::get('/projects', 'index');
        Route::post('/projects', 'create');
        Route::patch('/projects/{project}', 'update');
        Route::delete('/projects/{project}', 'destroy');
        Route::get('/user/my/projects/workspaces', 'myProjectPage');
    });




    // Claps  route
    Route::controller(ClapController::class)->group(function () {
        Route::post('/claps/{post}', 'clap');
        Route::delete('/claps/{post}', 'unclap');
    });

    // Likes  route
    Route::controller(LikeController::class)->group(function () {
        Route::post('/likes/{post}', 'like');
        Route::delete('/likes/{post}', 'unlike');
    });

    // Loves  route
    Route::controller(LoveController::class)->group(function () {
        Route::post('/loves/{post}', 'love');
        Route::delete('/loves/{post}', 'unlove');
    });

    // Comment and replies  route
    Route::controller(CommentsController::class)->group(function () {
        Route::post('/comments/{post}', 'comment');
        Route::delete('/comments/{post}', 'uncomment');
        Route::post('/replies/{comment}', 'reply');
        Route::delete('/replies/{comment}', 'unreply');
        Route::get('/comments/posts/{post}', 'postComments');
        Route::get('/replies/comments/{comment}', 'commentsReplies');
    });

    // Post view  route
    Route::controller(ViewController::class)->group(function () {
        Route::post('/views/{post}', 'view');
    });

    // User follow  route
    Route::controller(UserFollowingController::class)->group(function () {
        Route::post('/follow/user/{user}', 'follow');
        Route::delete('/unfollow/user/{user}', 'unfollow');
        Route::get('/myfollowers', 'myFollowers');
        Route::get('/amfollowing', 'amFollowing');
        Route::get('/myfollowing/workspace', 'myFollowingWorkspace');
    });

    // Business follow  route
    Route::controller(FollowingBusinessController::class)->group(function () {
        Route::post('/follow/business/{business}', 'follow');
        Route::delete('/unfollow/business/{business}', 'unfollow');
    });


    // Event follow  route
    Route::controller(FollowingEventController::class)->group(function () {
        Route::post('/follow/event/{event}', 'follow');
        Route::delete('/unfollow/event/{event}', 'unfollow');
    });


    // Project follow  route
    Route::controller(FollowingProjectController::class)->group(function () {
        Route::post('/follow/project/{project}', 'follow');
        Route::delete('/unfollow/project/{project}', 'unfollow');
    });

    // Auth route
    Route::controller(AuthController::class)->group(function () {
        Route::get('/users', 'indexOnLogin');
        Route::delete('/users/remove', 'deleteAccount');
        Route::post('/users/logout', 'logout');
        Route::get('/myworkspaces', 'myWorkspace');
        Route::get('/myworkspaces/business', 'myBusinessWorkspace');
        Route::get('/myworkspaces/projects', 'myProjectWorkspace');
        Route::get('/myworkspaces/events', 'myEventWorkspace');
        Route::get('/users/find/{email}', 'getUserByEmail');
        Route::post('/users/sendmail', 'sendMail');
        Route::patch('/user/password', 'changePasswordFromSetting');
    });

    // dashboard route
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard/users', 'index');
        Route::post('/dashboard/users', 'create');
        Route::delete('/dashboard/user/{user}', 'destroyUser');
        Route::patch('/dashboard/user/{user}', 'updateProfile');
        Route::patch('/dashboard/user/activate/{user}', 'activate');
        Route::post('/dashboard/invite', 'sendNonUserInvite');
    });

    // Post Controller
    Route::controller(PostController::class)->group(function () {
        Route::post('/post/save', 'create');
        Route::patch('/post/edit/{id}', 'update');
        Route::delete('/post/delete/{id}', 'delete');
        //        Route::get('/post', 'allPosts');
        //        Route::get('/post/info/{id}', 'postInfo');
        Route::get('/user/posts', 'myPosts');
        Route::get('/user/images/posts', 'myImagePosts');
        Route::get('/user/videos/posts', 'myVideoPosts');
        Route::post('/files', 'saveFile');
        Route::post('/chats/files', 'saveFileFromChat');
        //        Route::delete('/user/messagings/medias/saves/{mySave}', 'destroy');
        //        Route::get('/user/messagings/medias/saves', 'mySaveMedia');
        //        Route::get('/user/messagings/medias/saves/images', 'mySaveImage');
        //        Route::get('/user/messagings/medias/saves/videos', 'mySaveVideos');
        Route::post('/user/saves/posts/{post}', 'mySavePost');
        Route::delete('/user/saves/posts/{post}', 'destroySavePost');
        Route::get('/user/mypost/saves', 'mySavePostMedia');
        Route::get('/user/mypost/saves/images', 'mySavePostImage');
        Route::get('/user/mypost/saves/videos', 'mySavePostsVideo');
        Route::get('/user/privates/posts', 'myPrivatePosts');
        Route::get('/user/public/post', 'myPublicPosts');
    });

    // Complain Controller
    Route::controller(ComplainController::class)->group(function () {
        Route::post('/complain/post', 'reportPost');
        Route::delete('/complain/undo/{complain_id}', 'undoReport');
        Route::get('/complain', 'myComplains');
    });

    // Downloads
    Route::controller(DownloadController::class)->group(function () {
        Route::post('/downloads/increase/post/{post_id}', 'increase_count');
        Route::get('/mydownloads', 'myDownload');
        Route::get('/mydownloaders', 'myDownloader');
    });

    // Invite
    Route::controller(InviteController::class)->group(function () {
        Route::post('/page/invite', 'sendPageInvite');
        Route::post('/invite', 'profileInvite');
    });

    // Professional Info
    Route::controller(ProfessionalInfoController::class)->group(function () {
        Route::post('/professionalInfo', 'create');
        Route::get('/professionalInfo', 'index');
        Route::get('/professionalInfo/{user_id}', 'show');
        Route::patch('/professionalInfo', 'update');
        Route::delete('/professionalInfo/{professionalInfo}', 'destroy');
    });

    // User Project
    Route::controller(UserProjectController::class)->group(function () {
        Route::post('/userProject', 'create');
        Route::get('/userProject', 'index');
        Route::get('/userProject/{id}', 'show');
        Route::patch('/userProject/{id}', 'update');
        Route::delete('/userProject/{id}', 'delete');
    });

    // Profile Controller
    Route::controller(ProfileController::class)->group(function () {
        Route::get('user/myProfile', 'getMyProfileInfo');
        Route::patch('user/profileUpdate', 'updateProfile');
        Route::patch('user/visibility', 'changePrivacy');
        Route::patch('user/updatePicture', 'updateProfilePicture');
    });

    // MEDIA CONTROLLER
    Route::controller(MediaController::class)->group(function () {
        // Route::post('/media', 'save');
        // Route::get('/media/{path}', 'retrieve');
        Route::delete('remove', 'destroyFileUsingApi');
        // Route::delete('/removedir/{dir}', 'destroyDirectory');
    });

    // BIO CONTROLLER
    Route::controller(BioController::class)->group(function () {
        Route::post('/uploadBio', 'uploadPDF');
        Route::get('/bioPreview', 'bioPreview');
        Route::patch('/saveUpdate', 'saveUpdate');
        Route::get('/systemGenerateBio', 'generateBioFromProfileInformation');
        Route::get('/bio/download/{user_id}', 'downloadBio');
    });

    // PAYPAL CONTROLLER
    Route::controller(PayPalController::class)->group(function () {
        Route::post('/pay', 'processTransaction');
        Route::get('/pay/check', 'checkPaymentStatus');
    });

    // MUTE USER CONTROLLER
    Route::controller(BlockUserController::class)->group(function () {
        Route::post('mute/user/{user_id}', 'BlockUser');
        Route::post('unmute/user/{user_id}', 'UnblockUser');
        Route::get('mute/all', 'myBlockedUsers');
    });

    // MUTE USER CONTROLLER
    Route::controller(BlockPostController::class)->group(function () {
        Route::post('mute/post/{post_id}', 'BlockPost');
        Route::post('unmute/post/{post_id}', 'UnblockPost');
        Route::get('mute/post/all', 'myBlockedPosts');
    });
});

// BIO CONTROLLER
Route::controller(BioController::class)->group(function () {
    Route::get('/bio/download/{user_id}', 'downloadBio');
});

// Download media
Route::controller(DownloadController::class)->group(function () {
    Route::get('/downloads/post/{post_id}', 'download');
});

// Invite
Route::controller(InviteController::class)->group(function () {
    Route::get('/accept', 'acceptProfileInvite');
    Route::get('/invite/page', 'acceptPageInvite');
    Route::get('/profile/{token}', 'acceptProfileInvite');
});

// Create Account
Route::controller(SignupController::class)->group(function () {
    Route::post('/users', 'create');
    Route::post('/mails', 'mailValidation');
});

// Login
Route::controller(LoginController::class)->group(function () {
    Route::post('/users/login', 'login');
    Route::post('/googlelogin', 'googleLogin');
});

Route::controller(AppleController::class)->group(function () {
    Route::post('/apple/login', 'appleLogin');
    Route::post('/apple/callback', 'appleCallback');
});

// OTP
Route::controller(OtpController::class)->group(function () {
    Route::get('/verify', 'verifyOTP');
    Route::get('/password/reset', 'verifyPasswordOtp');
    Route::post('/otp/resend/{token}', 'resendOTP');
    Route::post('/password/resend/{token}', 'resendPasswordOTP');
});

// Auth
Route::controller(AuthController::class)->group(function () {
    Route::post('/verify/email', 'checkEmail');
    Route::post('/password/save', 'resetPassword');
    Route::get('/all/workspaces', 'allWorkspace');
    Route::get('/workspaces/download/business/{business_id}', 'downloadBusinessWorkSpaceDescription');
    Route::get('/workspaces/download/event/{event_id}', 'downloadEventWorkSpaceDescription');
    Route::get('/workspaces/download/project/{project_id}', 'downloadProjectWorkSpaceDescription');
    Route::get('/users/workspaces/business/{business}', 'myBusinessWorkspacePost');
    Route::get('/users/workspaces/event/{event}', 'myEventWorkspacePost');
    Route::get('/users/workspaces/project/{project}', 'myProjectWorkspacePost');
    Route::get('/users/workspaces/business/images/{business}', 'myBusinessWorkspaceImage');
    Route::get('/users/workspaces/event/images/{event}', 'myEventWorkspaceImage');
    Route::get('/users/workspaces/project/images/{project}', 'myProjectWorkspaceImage');
    Route::get('/users/workspaces/business/videos/{business}', 'myBusinessWorkspaceVideo');
    Route::get('/users/workspaces/event/videos/{event}', 'myEventWorkspaceVideo');
    Route::get('/users/workspaces/project/videos/{project}', 'myProjectWorkspaceVideo');
    Route::get('/users/workspaces/{user}', 'userWorkspace');
});

// Enquiries route
Route::controller(InquiryController::class)->group(function () {
    Route::post('/enquiries', 'create');
    Route::delete('/enquiries/{enquiry}', 'destroy');
});

// Business page  route
Route::controller(CategoryController::class)->group(function () {
    Route::post('/categories', 'create');
    Route::get('/categories/search', 'getCategories');
    Route::get('/categories', 'index');
    Route::get('/posts/categories/{category}', 'postOnCategory');
    Route::get('/categories/posts/images/{category}', 'postImageOnCategory');
    Route::get('/categories/posts/videos/{category}', 'postVideoOnCategory');
    Route::get('/category/posts/{category}', 'postOnCategories');
    Route::get('/category/posts/images/{category}', 'postImageOnCategories');
    Route::get('/category/posts/videos/{category}', 'postVideoOnCategories');
});

// News latter  route
Route::controller(NewsLetterController::class)->group(function () {
    Route::post('/newsletters', 'create');
});

//Careers  route
Route::controller(CarerController::class)->group(function () {
    Route::post('/carers', 'create');
    Route::post('/carers/{carer}', 'destroy');
});

//Carers  route
Route::controller(AuthController::class)->group(function () {
    Route::get('/front/users', 'indexUsers');
});

// Post Controller
Route::controller(PostController::class)->group(function () {
    Route::get('/post', 'allPosts');
    Route::get('/post/info/{id}', 'postInfo');
    Route::get('/keyword/search', 'fullKeywordSearch');
    Route::get('/post/featured', 'getFeaturedPost');
    Route::get('/post/user/{id}', 'userPosts');
});

// Industry  route
Route::controller(IndustryController::class)->group(function () {
    Route::post('/industries', 'create');
    Route::get('/industries/search', 'getIndustries');
    Route::get('/industries/all', 'allIndustries');
});

// Share Post  route
Route::controller(SharePostCountController::class)->group(function () {
    Route::post('/share/post/{post_id}', 'increaseShareCount');
});

// Complain Flag Controller
Route::controller(ComplainFlagController::class)->group(function () {
    Route::post('/flag/save', 'newFlag');
    Route::patch('/flag/{flag_id}', 'editFlag');
    Route::delete('/flag/{flag_id}', 'deleteFlag');
    Route::get('/flag/all', 'allFlags');
});

// project page  route
Route::controller(ProjectController::class)->group(function () {
    Route::get('/view/projects/workspaces/{project}', 'projectPageView');
    Route::get('/view/projects/workspaces/images/{project}', 'projectPageViewImage');
    Route::get('/view/projects/workspaces/videos/{project}', 'projectPageViewVideos');
});

// Event page  route
Route::controller(EventController::class)->group(function () {
    Route::get('/view/events/workspaces/{event}', 'eventPageView');
    Route::get('/view/events/workspaces/images/{event}', 'eventPageViewImage');
    Route::get('/view/events/workspaces/videos/{event}', 'eventPageViewVideos');
});

// business page  route
Route::controller(BusinessController::class)->group(function () {
    Route::get('/view/business/workspaces/{business}', 'businessPageView');
    Route::get('/view/business/workspaces/images/{business}', 'businessPageViewImage');
    Route::get('/view/business/workspaces/videos/{business}', 'businessPageViewVideos');
});

// Post view  route
Route::controller(ImpressionController::class)->group(function () {
    Route::post('/impression/{post}', 'impression');
});

// Profile Controller
Route::controller(ProfileController::class)->group(function () {
    Route::get('user/profile/{user_id}', 'view');
});

// Testing do not use
// Test controller / dummy controller
Route::controller(AuthController::class)->group(function () {
    Route::post('/delete/user', 'destroyUser');
});
Route::controller(DashboardController::class)->group(function () {
    Route::get('/dashboard/user/profile/{user}', 'profileInfo');
});
// END OF TEST
Route::controller(DummyController::class)->group(function () {
//     Route::delete('/dummy/post/{id}', 'destroyPost');
//     Route::post('/dummy/post/upload', 'UploadImage');
    Route::post('dummy/old/people', 'newUser');
    Route::delete('dummy/old/people', 'removeAllOfThem');
});
// Not to be used
