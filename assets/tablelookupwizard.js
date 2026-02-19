import './tablelookupwizard.scss';

import { Application } from '@hotwired/stimulus';
import WizardController from './controller';

const application = Application.start();
application.register('terminal42--tablelookupwizard', WizardController);
