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



Route::group(['middleware'=>'api'],function(){
    //GESTIONE LOGIN
    Route::post('login', [AuthController::class,'login']);
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);

    //GESTIONE UTENTE
    Route::get('me', [AuthController::class,'me']);
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

