<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\option_status_contract;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerDataController;
use App\Http\Controllers\MacroProductController;
use App\Http\Controllers\SpecificDataController;
use App\Http\Controllers\StatusContractController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\SupplierCategoryController;
use App\Http\Controllers\OptionStatusContractController;
use App\Http\Controllers\ContractTypeInformationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ContractDataOverviewController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LogSettingsController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// ============================================
// CONTRACT DATA OVERVIEW - Google Sheets Integration
// These routes use API key authentication (not JWT)
// ============================================
Route::prefix('contract-data-overview')->group(function () {
    Route::get('/', [ContractDataOverviewController::class, 'index']);
    Route::post('/bulk-update', [ContractDataOverviewController::class, 'bulkUpdate']);
    Route::get('/{id}', [ContractDataOverviewController::class, 'show']);
});


Route::group(['middleware'=>'api'],function(){
    
    //GESTIONE LOGIN
    Route::post('login', [AuthController::class,'login']);
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);

    //GESTIONE UTENTE
    Route::get('me', [AuthController::class,'me']);
    
    //GESTIONE WALLET
    Route::get('user/wallet', [WalletController::class, 'getWallet'])->name('wallet.get');
    Route::get('user/wallet/summary', [WalletController::class, 'getWalletSummary'])->name('wallet.summary');
    Route::get('user/wallet/history', [WalletController::class, 'getTransactionHistory'])->name('wallet.history');
    
    //Admin only routes for managing points
    Route::post('admin/wallet/add-bonus', [WalletController::class, 'addBonusPoints'])->name('wallet.addBonus');
    Route::post('admin/wallet/update-points', [WalletController::class, 'updatePointsAfterPurchase'])->name('wallet.updatePoints');
    
    Route::get('user/{email}',[RoleController::class,'prendiUtente'])->name('utente');
    Route::post('codFPIva',[AuthController::class,'codFPIva'])->name('codFPIva');
    Route::post('nuovoCliente',[AuthController::class,'nuovoCliente'])->name('nuovoCliente');
    Route::get('ruoliequalifiche',[RoleController::class,'ruoliequalifiche'])->name('ruoliequalifiche');
    Route::get('copiautente{id}',[AuthController::class,'copiautente'])->name('copiautente');
    Route::post('nuovoContraente',[CustomerDataController::class,'nuovoContraente'])->name('nuovoContraente');
    Route::get('getAllUser',[AuthController::class,'getAllUser'])->name('getAllUser');
    Route::get('recuperaSEU',[AuthController::class,'recuperaSEU'])->name('recuperaSEU');
    Route::post('updatePassw',[AuthController::class,'updatePassw'])->name('updatePassw');
    Route::post('updateUtente',[AuthController::class,'updateUtente'])->name('updateUtente');
    Route::post('dettagliUtente{id}',[AuthController::class,'dettagliUtente'])->name('dettagliUtente');

    //GESTIONE PRODOTTI
    Route::get('prodotti', [ProductController::class,'prodotti']);
    Route::get('getProdotto{id}',[ProductController::class,'getProdotto'])->name('getProdotto');
    Route::get('nuovoProdotto',[ProductController::class,'nuovoProdotto'])->name('nuovoProdotto');
    Route::post('getMacroProduct{id}',[MacroProductController::class,'getMacroProduct'])->name('getMacroProduct');
    Route::post('allMacroProduct{id}',[MacroProductController::class,'allMacroProduct'])->name('allMacroProduct');
    Route::post('GetallMacroProduct',[MacroProductController::class,'GetallMacroProduct'])->name('GetallMacroProduct');
    Route::post('storeNewProduct',[ProductController::class,'storeNewProduct'])->name('storeNewProduct');
    Route::post('disabilitaProdotto{id}',[ProductController::class,'disabilitaProdotto'])->name('disabilitaProdotto');
    Route::post('abilitaProdotto{id}',[ProductController::class,'abilitaProdotto'])->name('abilitaProdotto');
    Route::post('cancellaProdotto{id}',[ProductController::class,'cancellaProdotto'])->name('cancellaProdotto');
    Route::post('updateProdotto',[ProductController::class,'updateProdotto'])->name('updateProdotto');

    //GESTIONE MACROPRODOTTI UPDATE
    Route::post('updateMacroProdotto',[MacroProductController::class,'updateMacroProdotto'])->name('updateMacroProdotto');
    Route::post('creaNuovoMacroProdotto',[MacroProductController::class,'creaNuovoMacroProdotto'])->name('creaNuovoMacroProdotto');

    //GESTIONE CATEGORIE
    Route::get('macroCat', [MacroProductController::class,'macroCat']);

    //GESTIONE RUOLI
    Route::post('ruoli',[RoleController::class,'index'])->name('ruoli');
    Route::get('ruolo',[RoleController::class,'index2'])->name('ruolo');

    //GESTIONE CONTRATTI
    Route::get('contractType',[ContractTypeInformationController::class,'contractType'])->name('contractType');
    Route::get('getPagamentoSystem',[ContractController::class,'getPagamentoSystem'])->name('getPagamentoSystem');
    Route::post('getContratti{id}',[ContractController::class,'getContratti'])->name('getContratti');
    Route::post('searchContratti/{id}',[ContractController::class,'searchContratti'])->name('searchContratti');
    Route::post('getContratto{id}',[ContractController::class,'getContratto'])->name('getContratto');
    Route::post('nuovoContratto',[ContractController::class,'nuovoContratto'])->name('nuovoContratto');
    Route::post('updateContratto',[ContractController::class,'updateContratto'])->name('updateContratto');
    Route::get('getStatiAvanzamento',[ContractController::class,'getStatiAvanzamento'])->name('getStatiAvanzamento');
    Route::get('getMacroStatiAvanzamento',[ContractController::class,'getMacroStatiAvanzamento'])->name('getMacroStatiAvanzamento');
    Route::get('contrattiPersonali{id}',[ContractController::class,'contrattiPersonali'])->name('contrattiPersonali');
    Route::post('getContCodFPIva',[ContractController::class,'getContCodFPIva'])->name('getContCodFPIva');
    Route::post('controlloProdottoNeiContratti{id}',[ContractController::class,'controlloProdottoNeiContratti'])->name('controlloProdottoNeiContratti');
    Route::post('updateStatoMassivoContratti',[ContractController::class,'updateStatoMassivoContratti'])->name('updateStatoMassivoContratti');
    Route::get('getSupplier', [SupplierController::class, 'getSupplier'])->name('getSupplier');
    Route::get('getMacroStato', [OptionStatusContractController::class, 'getMacroStato'])->name('getMacroStato');
    Route::get('getStato', [StatusContractController::class, 'getStato'])->name('getStato');
    Route::post('getDomandeMacro/{id}', [ContractTypeInformationController::class, 'getDomandeMacro'])->name('getDomandeMacro');
    Route::post('getRisposteSelect/{id}', [ContractTypeInformationController::class, 'getRisposteSelect'])->name('getRisposteSelect');
    
    // GESTIONE TICKET
    Route::get('getTickets', [TicketController::class, 'getTickets'])->name('getTickets');
    Route::post('createTicket', [TicketController::class, 'createTicket'])->name('createTicket');
    Route::post('updateTicketStatus', [TicketController::class, 'updateTicketStatus'])->name('updateTicketStatus');
    Route::post('updateTicketPriority', [TicketController::class, 'updateTicketPriority'])->name('updateTicketPriority');
    Route::post('updateTicketCategory', [TicketController::class, 'updateTicketCategory'])->name('updateTicketCategory'); 
    Route::post('closeTicket', [TicketController::class, 'closeTicket'])->name('closeTicket');
    Route::post('bulkDeleteTickets', [TicketController::class, 'bulkDeleteTickets'])->name('bulkDeleteTickets');
    Route::get('getTicketMessages/{ticketId}', [TicketController::class, 'getTicketMessages'])->name('getTicketMessages');
    Route::post('sendTicketMessage', [TicketController::class, 'sendTicketMessage'])->name('sendTicketMessage');
    Route::get('getTicketChangeLogs/{ticketId}', [TicketController::class, 'getTicketChangeLogs'])->name('getTicketChangeLogs'); 
    Route::get('getTicketByContractId/{contractId}', [TicketController::class, 'getTicketByContractId']);
    Route::post('restoreTicket', [TicketController::class, 'restoreTicket'])->name('restoreTicket');
    Route::get('getAllTicketsByContractId/{contractId}', [TicketController::class, 'getAllTicketsByContractId'])->name('getAllTicketsByContractId');
    Route::post('deleteTicketByContractId', [TicketController::class, 'deleteTicketByContractId'])->name('deleteTicketByContractId');
    Route::post('restoreLastTicketByContractId', [TicketController::class, 'restoreLastTicketByContractId'])->name('restoreLastTicketByContractId');
    Route::post('tickets/attachments/upload', [TicketController::class, 'uploadAttachments']);
    Route::get('tickets/{ticket}/attachments', [TicketController::class, 'getTicketAttachments']);
    Route::get('attachments/{attachment}/download', [TicketController::class, 'downloadAttachment']);
    Route::delete('attachments/{attachment}', [TicketController::class, 'deleteAttachment']);

    // GESTIONE NOTIFICHE 
    Route::get('notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('notifications/read/all', [NotificationController::class, 'deleteAllRead'])->name('notifications.deleteAllRead');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // GESTIONE LOG
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('logs/stats', [LogController::class, 'getStats'])->name('logs.stats');
    Route::get('logs/sources', [LogController::class, 'getSources'])->name('logs.sources');
    Route::get('logs/volume', [LogController::class, 'getVolume'])->name('logs.volume');
    Route::get('logs/files', [LogController::class, 'getLogFiles'])->name('logs.files');
    Route::get('logs/export', [LogController::class, 'export'])->name('logs.export');
    Route::get('logs/file', [LogController::class, 'getFileContent'])->name('logs.file');
    Route::get('logs/filters', [LogController::class, 'getFilters'])->name('logs.filters');
    Route::get('logs/contract/{id}', [LogController::class, 'getContractHistory'])->name('logs.contract-history');
    Route::delete('logs/clear', [LogController::class, 'clearLogs'])->name('logs.clear');
    Route::get('logs/{id}', [LogController::class, 'show'])->name('logs.show');
    Route::delete('logs/{id}', [LogController::class, 'destroy'])->name('logs.destroy');

    // GESTIONE LOG SETTINGS
    Route::get('log-settings', [LogSettingsController::class, 'index'])->name('log-settings.index');
    Route::get('log-settings/cleanup-stats', [LogSettingsController::class, 'getCleanupStats'])->name('log-settings.cleanup-stats');
    Route::get('log-settings/{key}', [LogSettingsController::class, 'show'])->name('log-settings.show');
    Route::put('log-settings/{key}', [LogSettingsController::class, 'update'])->name('log-settings.update');
    Route::post('log-settings/bulk-update', [LogSettingsController::class, 'bulkUpdate'])->name('log-settings.bulk-update');
    Route::post('log-settings/reset', [LogSettingsController::class, 'resetToDefaults'])->name('log-settings.reset');
    Route::post('log-settings/run-cleanup', [LogSettingsController::class, 'runCleanup'])->name('log-settings.run-cleanup');

    //GESTIONE DOMANDE
    Route::get('getDomande{id}',[SpecificDataController::class,'getDomande'])->name('getDomande');
    Route::get('getListaDomande',[SpecificDataController::class, 'getListaDomande'])->name('getListaDomande');
    Route::post('salvaDomande',[SpecificDataController::class,'salvaDomande'])->name('salvaDomande');
    Route::post('deleteQuestion{id}',[SpecificDataController::class,'deleteQuestion'])->name('deleteQuestion');

    //GESTIONE IMMAGINI / DOCUMENTI
    Route::post('storeIMG',[AuthController::class,'storeIMG'])->name('storeIMG');
    Route::post('deleteIMG',[AuthController::class,'deleteIMG'])->name('deleteIMG');
    Route::post('attesaCaricamentoImmagini',[AuthController::class,'attesaCaricamentoImmagini'])->name('attesaCaricamentoImmagini');
    Route::post('immagineProfiloUtente',[AuthController::class,'uploadProfileImage'])->name('immagineProfiloUtente');
    Route::get('getFiles{id}',[AuthController::class,'getFiles'])->name('getFiles');

    //GESTIONE LEADS
    Route::post('storeNewLead',[LeadController::class,'storeNewLead'])->name('storeNewLead');
    Route::get('getLeads',[LeadController::class,'getLeads'])->name('getLeads');
    Route::get('getAppointments',[LeadController::class,'getAppointments'])->name('getAppointments');
    Route::post('updateLead',[LeadController::class,'updateLead'])->name('updateLead');
    Route::get('getStatiLeads',[LeadController::class,'getStatiLeads'])->name('getStatiLeads');
    Route::get('getUserForLeads',[LeadController::class,'getUserForLeads'])->name('getUserForLeads');
    Route::post('appuntamentoLead',[LeadController::class,'appuntamentoLead'])->name('appuntamentoLead');
    Route::post('updateAssegnazioneLead',[LeadController::class,'updateAssegnazioneLead'])->name('updateAssegnazioneLead');
    Route::post('getLeadsDayClicked',[LeadController::class,'getLeadsDayClicked'])->name('getLeadsDayClicked');
    Route::get('getColorRowStatusLead{id}',[LeadController::class,'getColorRowStatusLead'])->name('getColorRowStatusLead');
    Route::post('nuovoClienteLead',[LeadController::class,'nuovoClienteLead'])->name('nuovoClienteLead');

    //GESTIONE MESSAGGI
    Route::get('getMessageNotification',[AuthController::class,'getMessageNotification'])->name('getMessageNotification');
    Route::post('markReadMessage{id}',[AuthController::class,'markReadMessage'])->name('markReadMessage');

    //GESTIONE FORNITORI E CATEGORIE FORNITORI
    Route::get('recuperaCategorieFornitori',[SupplierCategoryController::class,'recuperaCategorieFornitori'])->name('recuperaCategorieFornitori');
    Route::post('nuovoFornitore',[SupplierController::class,'nuovoFornitore'])->name('nuovoFornitore');

});