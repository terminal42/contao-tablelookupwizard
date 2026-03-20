import './tablelookupwizard.scss';

import { Application, Controller } from '@hotwired/stimulus';

const CSS_CLASS_FETCHED = 'fetched';
const CSS_CLASS_LOADING = 'loading';

const application = Application.start();
application.debug = process.env.NODE_ENV === 'development';
application.register(
    'terminal42--tablelookupwizard',
    class extends Controller {
        static targets = ['results', 'search', 'selection'];

        static values = {
            multiple: Boolean,
            name: String,
        };

        // Prevent the form submission when hitting the enter on keywords input search
        preventSubmit(event) {
            event.preventDefault();
        }

        search(event) {
            clearTimeout(this.debounce);

            this.debounce = setTimeout(() => {
                const input = event.target;

                if (input.value === '') {
                    this.searchTarget.classList.remove(CSS_CLASS_FETCHED);

                    return;
                }

                input.classList.add(CSS_CLASS_LOADING);

                this.#fetchRecords(input.value).finally(() => {
                    this.searchTarget.classList.add(CSS_CLASS_FETCHED);
                    input.classList.remove(CSS_CLASS_LOADING);
                });
            }, 300);
        }

        select(event) {
            if (!this.multipleValue) {
                [...this.selectionTarget.children].forEach((child) => this.resultsTarget.prepend(child));
            }

            const row = event.currentTarget.closest('tr');

            this.selectionTarget.appendChild(row);
            this.#toggleInput(row, false);
        }

        unselect(event) {
            const row = event.currentTarget.closest('tr');

            this.resultsTarget.prepend(row);
            this.#toggleInput(row, true);
        }

        #toggleInput(row, disabled) {
            row.querySelector('input[type="hidden"]').disabled = disabled;
        }

        async #fetchRecords(keywords) {
            let html = '';

            const url = new URL(window.location.href);

            url.searchParams.set('tableLookupWizard', this.nameValue);
            url.searchParams.set('keywords', keywords);

            // Add it this way, so multiple values are also supported out of the box
            this.selectionTarget
                .querySelectorAll('input[type="hidden"]')
                .forEach((input) => url.searchParams.append(input.name, input.value));

            try {
                const response = await fetch(url.toString());
                html = await response.text();
            } catch (error) {
                // eslint-disable-next-line no-console
                console.error(`TableLookupWizard error: ${error}`);
            }

            this.resultsTarget.innerHTML = html;
        }
    },
);
