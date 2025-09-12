import { trigger, transition, style, animate } from '@angular/animations';
import { Component } from '@angular/core';

@Component({
    selector: 'typography-cmp',
    moduleId: module.id,
    templateUrl: 'typography.component.html',
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })) // Espandi e rendi visibile
            ]),
            transition(":leave", [
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })) // Riduci e rendi invisibile
            ])
        ])
    ],
    standalone: false
})

export class TypographyComponent{

  state = 'pagina1';
}
