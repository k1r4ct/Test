import { Routes } from '@angular/router';

import { DashboardComponent } from '../../pages/dashboard/dashboard.component';
import { UserComponent } from '../../pages/user/user.component';
import { TableComponent } from '../../pages/table/table.component';
import { TypographyComponent } from '../../pages/typography/typography.component';
import { IconsComponent } from '../../pages/icons/icons.component';
import { LeadsComponent } from '../../pages/leads/leads.component';
import { NotificationsComponent } from '../../pages/notifications/notifications.component';
import { UpgradeComponent } from '../../pages/upgrade/upgrade.component';
import { ContrattiComponent } from '../../pages/contratti/contratti.component';
import { FormGeneraleComponent } from 'src/app/form-generale/form-generale.component';
import { DomandeComponent } from 'src/app/pages/domande/domande.component';
import { DropzoneComponent } from 'ngx-dropzone-wrapper';


export const AdminLayoutRoutes: Routes = [
    /* { path: 'dashboard',      component: DashboardComponent },
    { path: 'user',           component: UserComponent,data: { cache: false } },
    { path: 'table',          component: TableComponent },
    { path: 'typography',     component: TypographyComponent },
    { path: 'icons',          component: IconsComponent },
    { path: 'leads',          component: LeadsComponent },
    { path: 'contratti',      component: ContrattiComponent,data: { cache: false } },
    { path: 'notifications',  component: NotificationsComponent },
    { path: 'upgrade',        component: UpgradeComponent },
    { path: 'Form',        component: FormGeneraleComponent },
    { path: 'gestionedomande',        component: DomandeComponent }, */
];
