-- semprechiaro_crm.api_tokens definition

CREATE TABLE `api_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.customer_datas definition

CREATE TABLE `customer_datas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `cognome` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `pec` varchar(255) DEFAULT NULL,
  `codice_fiscale` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `citta` varchar(255) DEFAULT NULL,
  `cap` varchar(255) DEFAULT NULL,
  `provincia` varchar(255) DEFAULT NULL,
  `nazione` varchar(255) DEFAULT NULL,
  `partita_iva` varchar(255) DEFAULT NULL,
  `ragione_sociale` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1933 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.failed_jobs definition

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.migrations definition

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=275 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.password_reset_tokens definition

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.payment_modes definition

CREATE TABLE `payment_modes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_pagamento` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.personal_access_tokens definition

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.qualifications definition

CREATE TABLE `qualifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `descrizione` text DEFAULT NULL,
  `pc_necessari` int(11) DEFAULT NULL,
  `compenso_pvdiretti` text DEFAULT NULL,
  `pc_bonus_mensile` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.roles definition

CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `descrizione` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.status_contracts definition

CREATE TABLE `status_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `micro_stato` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.supplier_categories definition

CREATE TABLE `supplier_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome_categoria` text NOT NULL,
  `descrizione` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.survey_type_informations definition

CREATE TABLE `survey_type_informations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domanda` text NOT NULL,
  `risposta_tipo_numero` decimal(10,2) NOT NULL,
  `risposta_tipo_stringa` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.table_colors definition

CREATE TABLE `table_colors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `colore` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.indirects definition

CREATE TABLE `indirects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_livello` int(11) NOT NULL,
  `percentuale_indiretta` int(11) NOT NULL,
  `qualification_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indirects_qualification_id_foreign` (`qualification_id`),
  CONSTRAINT `indirects_qualification_id_foreign` FOREIGN KEY (`qualification_id`) REFERENCES `qualifications` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.lead_statuses definition

CREATE TABLE `lead_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicabile_da_role_id` bigint(20) unsigned NOT NULL,
  `micro_stato` text NOT NULL,
  `macro_stato` text NOT NULL,
  `fase` text NOT NULL,
  `specifica` text NOT NULL,
  `color_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_statuses_applicabile_da_role_id_foreign` (`applicabile_da_role_id`),
  KEY `lead_statuses_color_id_foreign` (`color_id`),
  CONSTRAINT `lead_statuses_applicabile_da_role_id_foreign` FOREIGN KEY (`applicabile_da_role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `lead_statuses_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `table_colors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.macro_products definition

CREATE TABLE `macro_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codice_macro` text NOT NULL,
  `descrizione` text NOT NULL,
  `punti_valore` int(11) NOT NULL,
  `punti_carriera` int(11) NOT NULL,
  `supplier_category_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `macro_products_supplier_category_id_foreign` (`supplier_category_id`),
  CONSTRAINT `macro_products_supplier_category_id_foreign` FOREIGN KEY (`supplier_category_id`) REFERENCES `supplier_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.option_status_contracts definition

CREATE TABLE `option_status_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `macro_stato` text NOT NULL,
  `fase` text NOT NULL,
  `specifica` text NOT NULL,
  `genera_pv` int(11) NOT NULL,
  `genera_pc` int(11) NOT NULL,
  `status_contract_id` bigint(20) unsigned NOT NULL,
  `applicabile_da_role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `option_status_contracts_status_contract_id_foreign` (`status_contract_id`),
  KEY `option_status_contracts_applicabile_da_role_id_foreign` (`applicabile_da_role_id`),
  CONSTRAINT `option_status_contracts_applicabile_da_role_id_foreign` FOREIGN KEY (`applicabile_da_role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `option_status_contracts_status_contract_id_foreign` FOREIGN KEY (`status_contract_id`) REFERENCES `status_contracts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.suppliers definition

CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome_fornitore` text NOT NULL,
  `descrizione` text NOT NULL,
  `supplier_category_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppliers_supplier_category_id_foreign` (`supplier_category_id`),
  CONSTRAINT `suppliers_supplier_category_id_foreign` FOREIGN KEY (`supplier_category_id`) REFERENCES `supplier_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.users definition

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `qualification_id` bigint(20) unsigned NOT NULL,
  `codice` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `cognome` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `pec` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `codice_fiscale` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `cellulare` varchar(255) DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `citta` varchar(255) DEFAULT NULL,
  `cap` varchar(255) DEFAULT NULL,
  `provincia` varchar(255) DEFAULT NULL,
  `nazione` varchar(255) DEFAULT NULL,
  `stato_user` int(11) DEFAULT NULL,
  `punti_valore_maturati` int(11) DEFAULT NULL,
  `punti_carriera_maturati` int(11) DEFAULT NULL,
  `user_id_padre` int(11) DEFAULT NULL,
  `ragione_sociale` text DEFAULT NULL,
  `partita_iva` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_codice_fiscale_unique` (`codice_fiscale`),
  KEY `users_role_id_foreign` (`role_id`),
  KEY `users_qualification_id_foreign` (`qualification_id`),
  CONSTRAINT `users_qualification_id_foreign` FOREIGN KEY (`qualification_id`) REFERENCES `qualifications` (`id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1350 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.contract_managements definition

CREATE TABLE `contract_managements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `macro_product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_management_user_id_foreign` (`user_id`),
  KEY `contract_management_macro_product_id_foreign` (`macro_product_id`),
  CONSTRAINT `contract_management_macro_product_id_foreign` FOREIGN KEY (`macro_product_id`) REFERENCES `macro_products` (`id`),
  CONSTRAINT `contract_management_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=486 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.contract_type_informations definition

CREATE TABLE `contract_type_informations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `macro_product_id` bigint(20) unsigned NOT NULL,
  `domanda` text NOT NULL,
  `tipo_risposta` text NOT NULL,
  `obbligatorio` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_type_informations_macro_product_id_foreign` (`macro_product_id`),
  CONSTRAINT `contract_type_informations_macro_product_id_foreign` FOREIGN KEY (`macro_product_id`) REFERENCES `macro_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.detail_questions definition

CREATE TABLE `detail_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contract_type_information_id` bigint(20) unsigned NOT NULL,
  `opzione` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detail_questions_contract_type_information_id_foreign` (`contract_type_information_id`),
  CONSTRAINT `detail_questions_contract_type_information_id_foreign` FOREIGN KEY (`contract_type_information_id`) REFERENCES `contract_type_informations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.leads definition

CREATE TABLE `leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invitato_da_user_id` bigint(20) unsigned NOT NULL,
  `nome` text NOT NULL,
  `cognome` text NOT NULL,
  `telefono` text NOT NULL,
  `email` text NOT NULL,
  `lead_status_id` bigint(20) unsigned NOT NULL,
  `assegnato_a` bigint(20) unsigned NOT NULL,
  `data_appuntamento` datetime DEFAULT NULL,
  `ora_appuntamento` time DEFAULT NULL,
  `note` time DEFAULT NULL,
  `consenso` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_invitato_da_user_id_foreign` (`invitato_da_user_id`),
  KEY `leads_lead_status_id_foreign` (`lead_status_id`),
  KEY `leads_assegnato_a_foreign` (`assegnato_a`),
  CONSTRAINT `leads_assegnato_a_foreign` FOREIGN KEY (`assegnato_a`) REFERENCES `users` (`id`),
  CONSTRAINT `leads_invitato_da_user_id_foreign` FOREIGN KEY (`invitato_da_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `leads_lead_status_id_foreign` FOREIGN KEY (`lead_status_id`) REFERENCES `lead_statuses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=212 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.logs definition

CREATE TABLE `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_di_operazione` text NOT NULL,
  `datetime` datetime NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logs_user_id_foreign` (`user_id`),
  CONSTRAINT `logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3780 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.notifications definition

CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_user_id` bigint(20) unsigned NOT NULL,
  `to_user_id` bigint(20) unsigned NOT NULL,
  `reparto` text DEFAULT NULL,
  `notifica` text DEFAULT NULL,
  `visualizzato` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `notifica_html` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_from_user_id_foreign` (`from_user_id`),
  KEY `notifications_to_user_id_foreign` (`to_user_id`),
  CONSTRAINT `notifications_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.products definition

CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `descrizione` text NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `punti_valore` int(11) NOT NULL,
  `punti_carriera` int(11) NOT NULL,
  `attivo` int(11) NOT NULL,
  `macro_product_id` bigint(20) unsigned NOT NULL,
  `gettone` decimal(10,2) NOT NULL,
  `inizio_offerta` text DEFAULT NULL,
  `fine_offerta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_supplier_id_foreign` (`supplier_id`),
  KEY `products_macro_product_id_foreign` (`macro_product_id`),
  CONSTRAINT `products_macro_product_id_foreign` FOREIGN KEY (`macro_product_id`) REFERENCES `macro_products` (`id`),
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.surveys definition

CREATE TABLE `surveys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `domanda` text NOT NULL,
  `tipo_risposta` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `surveys_user_id_foreign` (`user_id`),
  CONSTRAINT `surveys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.contracts definition

CREATE TABLE `contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codice_contratto` text NOT NULL,
  `inserito_da_user_id` bigint(20) unsigned NOT NULL,
  `associato_a_user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `customer_data_id` bigint(20) unsigned NOT NULL,
  `data_inserimento` date NOT NULL,
  `data_stipula` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_mode_id` bigint(20) unsigned NOT NULL,
  `status_contract_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_inserito_da_user_id_foreign` (`inserito_da_user_id`),
  KEY `contracts_associato_a_user_id_foreign` (`associato_a_user_id`),
  KEY `contracts_product_id_foreign` (`product_id`),
  KEY `contracts_customer_data_id_foreign` (`customer_data_id`),
  KEY `contracts_payment_mode_id_foreign` (`payment_mode_id`),
  KEY `contracts_status_contract_id_foreign` (`status_contract_id`),
  CONSTRAINT `contracts_associato_a_user_id_foreign` FOREIGN KEY (`associato_a_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `contracts_customer_data_id_foreign` FOREIGN KEY (`customer_data_id`) REFERENCES `customer_datas` (`id`),
  CONSTRAINT `contracts_inserito_da_user_id_foreign` FOREIGN KEY (`inserito_da_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `contracts_payment_mode_id_foreign` FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes` (`id`),
  CONSTRAINT `contracts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `contracts_status_contract_id_foreign` FOREIGN KEY (`status_contract_id`) REFERENCES `status_contracts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1772 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.document_datas definition

CREATE TABLE `document_datas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` text NOT NULL,
  `descrizione` text NOT NULL,
  `path_storage` text NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_datas_contract_id_foreign` (`contract_id`),
  CONSTRAINT `document_datas_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.lead_converteds definition

CREATE TABLE `lead_converteds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_converteds_lead_id_foreign` (`lead_id`),
  KEY `lead_converteds_cliente_id_foreign` (`cliente_id`),
  CONSTRAINT `lead_converteds_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`),
  CONSTRAINT `lead_converteds_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.specific_datas definition

CREATE TABLE `specific_datas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domanda` text NOT NULL,
  `risposta_tipo_numero` decimal(10,2) DEFAULT NULL,
  `risposta_tipo_stringa` text DEFAULT NULL,
  `risposta_tipo_bool` tinyint(1) DEFAULT NULL,
  `tipo_risposta` varchar(100) DEFAULT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `specific_datas_contract_id_foreign` (`contract_id`),
  CONSTRAINT `specific_datas_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12913 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- semprechiaro_crm.backoffice_notes definition

CREATE TABLE `backoffice_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint(20) unsigned NOT NULL,
  `nota` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backoffice_notes_contract_id_foreign` (`contract_id`),
  CONSTRAINT `backoffice_notes_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;