import { TestBed } from '@angular/core/testing';

import { DropzoneDeactivateGuardService } from './dropzone-deactivate-guard.service';

describe('DropzoneDeactivateGuardService', () => {
  let service: DropzoneDeactivateGuardService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DropzoneDeactivateGuardService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
