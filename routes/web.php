<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group([
    'prefix' => 'admin',
  ],
  function() {    
    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
    Route::get('/botusers', [App\Http\Controllers\AdminController::class, 'botuserList'])->name('botuserList');
    Route::any('/botuser/create', [App\Http\Controllers\AdminController::class, 'botuserCreate'])->name('botuser.create');
    Route::any('/botuser/update/{id}', [App\Http\Controllers\AdminController::class, 'botuserUpdate'])->name('botuser.update');
    Route::post('/botuser/delete', [App\Http\Controllers\AdminController::class, 'botuserDelete'])->name('botuser.delete');
    Route::get('/gameroles', [App\Http\Controllers\AdminController::class, 'gameroleList'])->name('gameroleList');
    Route::any('/gamerole/create', [App\Http\Controllers\AdminController::class, 'gameroleCreate'])->name('gamerole.create');
    Route::any('/gamerole/update/{id}', [App\Http\Controllers\AdminController::class, 'gameroleUpdate'])->name('gamerole.update');
    Route::post('/gamerole/delete', [App\Http\Controllers\AdminController::class, 'gameroleDelete'])->name('gamerole.delete');
    Route::get('/currencys', [App\Http\Controllers\AdminController::class, 'currencyList'])->name('currencyList');
    Route::any('/currency/create', [App\Http\Controllers\AdminController::class, 'currencyCreate'])->name('currency.create');
    Route::any('/currency/update/{id}', [App\Http\Controllers\AdminController::class, 'currencyUpdate'])->name('currency.update');
    Route::post('/currency/delete', [App\Http\Controllers\AdminController::class, 'currencyDelete'])->name('currency.delete');
    Route::get('/botgroups', [App\Http\Controllers\AdminController::class, 'botgroupList'])->name('botgroupList');
    Route::any('/botgroup/create', [App\Http\Controllers\AdminController::class, 'botgroupCreate'])->name('botgroup.create');
    Route::any('/botgroup/update/{id}', [App\Http\Controllers\AdminController::class, 'botgroupUpdate'])->name('botgroup.update');
    Route::post('/botgroup/delete', [App\Http\Controllers\AdminController::class, 'botgroupDelete'])->name('botgroup.delete');
    Route::get('/sendcurhistorys', [App\Http\Controllers\AdminController::class, 'sendcurhistoryList'])->name('sendcurhistoryList');
    Route::any('/sendcurhistory/create', [App\Http\Controllers\AdminController::class, 'sendcurhistoryCreate'])->name('sendcurhistory.create');
    Route::any('/sendcurhistory/update/{id}', [App\Http\Controllers\AdminController::class, 'sendcurhistoryUpdate'])->name('sendcurhistory.update');
    Route::post('/sendcurhistory/delete', [App\Http\Controllers\AdminController::class, 'sendcurhistoryDelete'])->name('sendcurhistory.delete');
    Route::get('/chatmembers', [App\Http\Controllers\AdminController::class, 'chatmemberList'])->name('chatmemberList');
    Route::any('/chatmember/create', [App\Http\Controllers\AdminController::class, 'chatmemberCreate'])->name('chatmember.create');
    Route::any('/chatmember/update/{id}', [App\Http\Controllers\AdminController::class, 'chatmemberUpdate'])->name('chatmember.update');
    Route::post('/chatmember/delete', [App\Http\Controllers\AdminController::class, 'chatmemberDelete'])->name('chatmember.delete');
    Route::get('/roletypes', [App\Http\Controllers\AdminController::class, 'roletypeList'])->name('roletypeList');
    Route::any('/roletype/create', [App\Http\Controllers\AdminController::class, 'roletypeCreate'])->name('roletype.create');
    Route::any('/roletype/update/{id}', [App\Http\Controllers\AdminController::class, 'roletypeUpdate'])->name('roletype.update');
    Route::post('/roletype/delete', [App\Http\Controllers\AdminController::class, 'roletypeDelete'])->name('roletype.delete');
    Route::get('/gamerolesorders', [App\Http\Controllers\AdminController::class, 'gamerolesorderList'])->name('gamerolesorderList');
    Route::any('/gamerolesorder/create', [App\Http\Controllers\AdminController::class, 'gamerolesorderCreate'])->name('gamerolesorder.create');
    Route::any('/gamerolesorder/update/{id}', [App\Http\Controllers\AdminController::class, 'gamerolesorderUpdate'])->name('gamerolesorder.update');
    Route::post('/gamerolesorder/delete', [App\Http\Controllers\AdminController::class, 'gamerolesorderDelete'])->name('gamerolesorder.delete');
    Route::get('/tasks', [App\Http\Controllers\AdminController::class, 'taskList'])->name('taskList');
    Route::any('/task/create', [App\Http\Controllers\AdminController::class, 'taskCreate'])->name('task.create');
    Route::any('/task/update/{id}', [App\Http\Controllers\AdminController::class, 'taskUpdate'])->name('task.update');
    Route::post('/task/delete', [App\Http\Controllers\AdminController::class, 'taskDelete'])->name('task.delete');
    Route::get('/settings', [App\Http\Controllers\AdminController::class, 'settingList'])->name('settingList');
    Route::any('/setting/create', [App\Http\Controllers\AdminController::class, 'settingCreate'])->name('setting.create');
    Route::any('/setting/update/{id}', [App\Http\Controllers\AdminController::class, 'settingUpdate'])->name('setting.update');
    Route::post('/setting/delete', [App\Http\Controllers\AdminController::class, 'settingDelete'])->name('setting.delete');
    Route::get('/voitings', [App\Http\Controllers\AdminController::class, 'voitingList'])->name('voitingList');
    Route::any('/voiting/create', [App\Http\Controllers\AdminController::class, 'voitingCreate'])->name('voiting.create');
    Route::any('/voiting/update/{id}', [App\Http\Controllers\AdminController::class, 'voitingUpdate'])->name('voiting.update');
    Route::post('/voiting/delete', [App\Http\Controllers\AdminController::class, 'voitingDelete'])->name('voiting.delete');
    Route::get('/votes', [App\Http\Controllers\AdminController::class, 'voteList'])->name('voteList');
    Route::any('/vote/create', [App\Http\Controllers\AdminController::class, 'voteCreate'])->name('vote.create');
    Route::any('/vote/update/{id}', [App\Http\Controllers\AdminController::class, 'voteUpdate'])->name('vote.update');
    Route::post('/vote/delete', [App\Http\Controllers\AdminController::class, 'voteDelete'])->name('vote.delete');
    Route::get('/yesnovotes', [App\Http\Controllers\AdminController::class, 'yesnovoteList'])->name('yesnovoteList');
    Route::any('/yesnovote/create', [App\Http\Controllers\AdminController::class, 'yesnovoteCreate'])->name('yesnovote.create');
    Route::any('/yesnovote/update/{id}', [App\Http\Controllers\AdminController::class, 'yesnovoteUpdate'])->name('yesnovote.update');
    Route::post('/yesnovote/delete', [App\Http\Controllers\AdminController::class, 'yesnovoteDelete'])->name('yesnovote.delete');
    Route::get('/test', [App\Http\Controllers\AdminController::class, 'test']);
    Route::get('/rolesneedfromsaves', [App\Http\Controllers\AdminController::class, 'rolesneedfromsaveList'])->name('rolesneedfromsaveList');
    Route::any('/rolesneedfromsave/create', [App\Http\Controllers\AdminController::class, 'rolesneedfromsaveCreate'])->name('rolesneedfromsave.create');
    Route::any('/rolesneedfromsave/update/{id}', [App\Http\Controllers\AdminController::class, 'rolesneedfromsaveUpdate'])->name('rolesneedfromsave.update');
    Route::post('/rolesneedfromsave/delete', [App\Http\Controllers\AdminController::class, 'rolesneedfromsaveDelete'])->name('rolesneedfromsave.delete');
    Route::get('/sleepkillroles', [App\Http\Controllers\AdminController::class, 'sleepkillroleList'])->name('sleepkillroleList');
    Route::any('/sleepkillrole/create', [App\Http\Controllers\AdminController::class, 'sleepkillroleCreate'])->name('sleepkillrole.create');
    Route::any('/sleepkillrole/update/{id}', [App\Http\Controllers\AdminController::class, 'sleepkillroleUpdate'])->name('sleepkillrole.update');
    Route::post('/sleepkillrole/delete', [App\Http\Controllers\AdminController::class, 'sleepkillroleDelete'])->name('sleepkillrole.delete');
    Route::get('/bafs', [App\Http\Controllers\AdminController::class, 'bafList'])->name('bafList');
    Route::any('/baf/create', [App\Http\Controllers\AdminController::class, 'bafCreate'])->name('baf.create');
    Route::any('/baf/update/{id}', [App\Http\Controllers\AdminController::class, 'bafUpdate'])->name('baf.update');
    Route::post('/baf/delete', [App\Http\Controllers\AdminController::class, 'bafDelete'])->name('baf.delete');
    Route::get('/achievements', [App\Http\Controllers\AdminController::class, 'achievementList'])->name('achievementList');
    Route::any('/achievement/create', [App\Http\Controllers\AdminController::class, 'achievementCreate'])->name('achievement.create');
    Route::any('/achievement/update/{id}', [App\Http\Controllers\AdminController::class, 'achievementUpdate'])->name('achievement.update');
    Route::post('/achievement/delete', [App\Http\Controllers\AdminController::class, 'achievementDelete'])->name('achievement.delete');
    Route::get('/products', [App\Http\Controllers\AdminController::class, 'productList'])->name('productList');
    Route::any('/product/create', [App\Http\Controllers\AdminController::class, 'productCreate'])->name('product.create');
    Route::any('/product/update/{id}', [App\Http\Controllers\AdminController::class, 'productUpdate'])->name('product.update');
    Route::post('/product/delete', [App\Http\Controllers\AdminController::class, 'productDelete'])->name('product.delete');
    Route::get('/warningtypes', [App\Http\Controllers\AdminController::class, 'warningtypeList'])->name('warningtypeList');
    Route::any('/warningtype/create', [App\Http\Controllers\AdminController::class, 'warningtypeCreate'])->name('warningtype.create');
    Route::any('/warningtype/update/{id}', [App\Http\Controllers\AdminController::class, 'warningtypeUpdate'])->name('warningtype.update');
    Route::post('/warningtype/delete', [App\Http\Controllers\AdminController::class, 'warningtypeDelete'])->name('warningtype.delete');
    Route::get('/warningwords', [App\Http\Controllers\AdminController::class, 'warningwordList'])->name('warningwordList');
    Route::any('/warningword/create', [App\Http\Controllers\AdminController::class, 'warningwordCreate'])->name('warningword.create');
    Route::any('/warningword/update/{id}', [App\Http\Controllers\AdminController::class, 'warningwordUpdate'])->name('warningword.update');
    Route::post('/warningword/delete', [App\Http\Controllers\AdminController::class, 'warningwordDelete'])->name('warningword.delete');
    Route::get('/roleactions', [App\Http\Controllers\AdminController::class, 'roleactionList'])->name('roleactionList');
    Route::any('/roleaction/create', [App\Http\Controllers\AdminController::class, 'roleactionCreate'])->name('roleaction.create');
    Route::any('/roleaction/update/{id}', [App\Http\Controllers\AdminController::class, 'roleactionUpdate'])->name('roleaction.update');
    Route::post('/roleaction/delete', [App\Http\Controllers\AdminController::class, 'roleactionDelete'])->name('roleaction.delete');
    Route::get('/buyroles', [App\Http\Controllers\AdminController::class, 'buyroleList'])->name('buyroleList');
    Route::any('/buyrole/create', [App\Http\Controllers\AdminController::class, 'buyroleCreate'])->name('buyrole.create');
    Route::any('/buyrole/update/{id}', [App\Http\Controllers\AdminController::class, 'buyroleUpdate'])->name('buyrole.update');
    Route::post('/buyrole/delete', [App\Http\Controllers\AdminController::class, 'buyroleDelete'])->name('buyrole.delete');
    Route::get('/offers', [App\Http\Controllers\AdminController::class, 'offerList'])->name('offerList');
    Route::any('/offer/create', [App\Http\Controllers\AdminController::class, 'offerCreate'])->name('offer.create');
    Route::any('/offer/update/{id}', [App\Http\Controllers\AdminController::class, 'offerUpdate'])->name('offer.update');
    Route::post('/offer/delete', [App\Http\Controllers\AdminController::class, 'offerDelete'])->name('offer.delete');
    Route::get('/currencyrates', [App\Http\Controllers\AdminController::class, 'currencyrateList'])->name('currencyrateList');
    Route::any('/currencyrate/create', [App\Http\Controllers\AdminController::class, 'currencyrateCreate'])->name('currencyrate.create');
    Route::any('/currencyrate/update/{id}', [App\Http\Controllers\AdminController::class, 'currencyrateUpdate'])->name('currencyrate.update');
    Route::post('/currencyrate/delete', [App\Http\Controllers\AdminController::class, 'currencyrateDelete'])->name('currencyrate.delete');
  }
);
Route::any('/bot/asdui3980-dfuosdi4', [App\Http\Controllers\BotController::class, 'index']);
Route::any('/webhook/freekassa', [App\Http\Controllers\BotController::class, 'webhookFreekassa'])->name('freekassa.notice');
Route::get('/start-pay/{offer}', [App\Http\Controllers\BotController::class, 'paymentStart'])->name('payment.start');
Route::post('/create-pay', [App\Http\Controllers\BotController::class, 'paymentCreate'])->name('payment.create');
Route::get('/success-pay', [App\Http\Controllers\BotController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/test-pay', [App\Http\Controllers\BotController::class, 'testPay']);
Route::get('/fail-pay', [App\Http\Controllers\BotController::class, 'paymentFail'])->name('payment.fail');
Route::get('/bot-test',[App\Http\Controllers\BotController::class, 'test']);


Route::get('/grouptarifs', [App\Http\Controllers\AdminController::class, 'grouptarifList'])->name('grouptarifList');
Route::any('/grouptarif/create', [App\Http\Controllers\AdminController::class, 'grouptarifCreate'])->name('grouptarif.create');
Route::any('/grouptarif/update/{id}', [App\Http\Controllers\AdminController::class, 'grouptarifUpdate'])->name('grouptarif.update');
Route::post('/grouptarif/delete', [App\Http\Controllers\AdminController::class, 'grouptarifDelete'])->name('grouptarif.delete');
Route::get('/rewardhistorys', [App\Http\Controllers\AdminController::class, 'rewardhistoryList'])->name('rewardhistoryList');
Route::any('/rewardhistory/create', [App\Http\Controllers\AdminController::class, 'rewardhistoryCreate'])->name('rewardhistory.create');
Route::any('/rewardhistory/update/{id}', [App\Http\Controllers\AdminController::class, 'rewardhistoryUpdate'])->name('rewardhistory.update');
Route::post('/rewardhistory/delete', [App\Http\Controllers\AdminController::class, 'rewardhistoryDelete'])->name('rewardhistory.delete');
Route::get('/withdrawals', [App\Http\Controllers\AdminController::class, 'withdrawalList'])->name('withdrawalList');
Route::any('/withdrawal/create', [App\Http\Controllers\AdminController::class, 'withdrawalCreate'])->name('withdrawal.create');
Route::any('/withdrawal/update/{id}', [App\Http\Controllers\AdminController::class, 'withdrawalUpdate'])->name('withdrawal.update');
Route::post('/withdrawal/delete', [App\Http\Controllers\AdminController::class, 'withdrawalDelete'])->name('withdrawal.delete');
Route::get('/newsletters', [App\Http\Controllers\AdminController::class, 'newsletterList'])->name('newsletterList');
Route::any('/newsletter/create', [App\Http\Controllers\AdminController::class, 'newsletterCreate'])->name('newsletter.create');
Route::any('/newsletter/update/{id}', [App\Http\Controllers\AdminController::class, 'newsletterUpdate'])->name('newsletter.update');
Route::post('/newsletter/delete', [App\Http\Controllers\AdminController::class, 'newsletterDelete'])->name('newsletter.delete');
Route::get('/newslettertypes', [App\Http\Controllers\AdminController::class, 'newslettertypeList'])->name('newslettertypeList');
Route::any('/newslettertype/create', [App\Http\Controllers\AdminController::class, 'newslettertypeCreate'])->name('newslettertype.create');
Route::any('/newslettertype/update/{id}', [App\Http\Controllers\AdminController::class, 'newslettertypeUpdate'])->name('newslettertype.update');
Route::post('/newslettertype/delete', [App\Http\Controllers\AdminController::class, 'newslettertypeDelete'])->name('newslettertype.delete');
Route::get('/roulettesprizes', [App\Http\Controllers\AdminController::class, 'roulettesprizeList'])->name('roulettesprizeList');
Route::any('/roulettesprize/create', [App\Http\Controllers\AdminController::class, 'roulettesprizeCreate'])->name('roulettesprize.create');
Route::any('/roulettesprize/update/{id}', [App\Http\Controllers\AdminController::class, 'roulettesprizeUpdate'])->name('roulettesprize.update');
Route::post('/roulettesprize/delete', [App\Http\Controllers\AdminController::class, 'roulettesprizeDelete'])->name('roulettesprize.delete');