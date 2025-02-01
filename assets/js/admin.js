jQuery(document).ready(function($) {
    const ScriptDetective = {
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        cacheElements: function() {
            this.$metabox = $('#scriptdetective-metabox');
            this.$scanBtn = $('#scriptdetective-scan-btn');
            this.$results = $('#scriptdetective-results');
            this.$loading = $('#scriptdetective-loading');
            this.$filter = $('.scriptdetective-filter');
        },

        bindEvents: function() {
            this.$scanBtn.on('click', this.scanPage.bind(this));
            this.$results.on('click', '.script-toggle-details', this.toggleDetails.bind(this));
            this.$metabox.on('change', '.filter-type', this.filterScripts.bind(this));
            this.$metabox.on('change', '.script-switch input', this.toggleScript.bind(this));
        },

        scanPage: function(e) {
            e.preventDefault();
            const self = this;
            
            self.$scanBtn.prop('disabled', true);
            self.$loading.show();
            self.$results.html('');

            $.ajax({
                url: scriptdetective.ajax_url,
                type: 'GET',
                data: {
                    action: 'scriptdetective_scan',
                    post_id: scriptdetective.post_id,
                    security: scriptdetective.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayResults(response.data);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.data 
                               ? xhr.responseJSON.data 
                               : scriptdetective.labels.error;
                    self.showError(error);
                },
                complete: function() {
                    self.$scanBtn.prop('disabled', false);
                    self.$loading.hide();
                }
            });
        },

        displayResults: function(scripts) {
            this.$results.html(this.buildResultsHtml(scripts));
            this.applyFilters();
        },

        buildResultsHtml: function(scripts) {
            if (!scripts.length) return this.getNoScriptsTemplate();
            
            return `
                <div class="scriptdetective-results-header">
                    <h4>${scriptdetective.labels.detected_scripts} (${scripts.length})</h4>
                    <div class="scriptdetective-filter">
                        <label><input type="checkbox" class="filter-type" value="wordpress" checked> ${scriptdetective.labels.wordpress}</label>
                        <label><input type="checkbox" class="filter-type" value="external" checked> ${scriptdetective.labels.external}</label>
                    </div>
                </div>
                <ul class="scriptdetective-list">
                    ${scripts.map(script => this.scriptItemTemplate(script)).join('')}
                </ul>`;
        },

        scriptItemTemplate: function(script) {
            const typeClass = script.type === 'wordpress' ? 'wp' : 'external';
            const disabledClass = script.disabled ? 'disabled' : '';
            const scriptId = script.handle || script.src;
            const missingClass = script.missing ? 'missing' : '';

            console.log('script data', script)
            return `
                <li class="script-item ${script.type} ${disabledClass} ${missingClass}" 
                    data-script-id="${scriptId}"
                    data-script-type="${script.type}"
                    data-script-version="${script.version}"
                   >
                    ${script.missing ? `
                    <div class="script-warning">
                        <span class="dashicons dashicons-warning"></span>
                        ${scriptdetective.labels.missing_warning}
                    </div>` : ''}
                    <div class="script-header">
                        <span class="script-type ${typeClass}">${scriptdetective.labels[script.type]}</span>
                        <div class="script-controls">
                            <label class="script-switch">
                                <input type="checkbox" ${script.disabled ? '' : 'checked'}>
                                <span class="slider"></span>
                            </label>
                            <button class="script-toggle-details button-link" type="button">
                                <span class="dashicons dashicons-arrow-down"></span>
                            </button>
                        </div>
                    </div>
                    <div class="script-main-info">
                        <div class="script-handle">${script.handle || scriptdetective.labels.anonymous}</div>
                        <div class="script-src">${script.src}${script.version ? '?ver=' + script.version : ''}</div>
                    </div>
                    <div class="script-details">
                        ${this.scriptDetailsTemplate(script)}
                    </div>
                </li>`;
        },

        scriptDetailsTemplate: function(script) {
            return `
                ${script.deps?.length ? `
                <div class="script-detail">
                    <label>${scriptdetective.labels.dependencies}:</label>
                    <span>${script.deps.join(', ')}</span>
                </div>` : ''}
                
                <div class="script-detail">
                    <label>${scriptdetective.labels.version}:</label>
                    <span>${script.version || (script.src.match(/ver=([^&]*)/)?.[1] || 'N/A')}</span>
                </div>
                <div class="script-detail">
                    <label>${scriptdetective.labels.in_footer}:</label>
                    <span>${script.in_footer ? '✓' : '✗'}</span>
                </div>
                <div class="script-detail">
                <label>${scriptdetective.labels.size}:</label>
                <span class="script-size" data-src="${script.src}">
                    ${script.size ? scriptdetective.formatSize(script.size) : 'N/A'}
                    ${script.size === 0 ? '<span class="size-error" title="Could not retrieve size"></span>' : ''}
                </span>
            </div>
                `;
        },

        toggleDetails: function(e) {
            const $btn = $(e.currentTarget);
            const $details = $btn.closest('.script-item').find('.script-details');
            $details.slideToggle(200);
            $btn.find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
        },

        toggleScript: function(e) {
            const $input = $(e.target);
            const $item = $input.closest('.script-item');
            const scriptId = $item.data('script-id');
            const scriptType = $item.data('script-type');
            const scriptVersion = $item.data('script-version');
            const isEnabled = $input.prop('checked');

            $item.addClass('updating');

            $.ajax({
                url: scriptdetective.ajax_url,
                method: 'POST',
                data: {
                    action: 'scriptdetective_toggle_script',
                    security: scriptdetective.nonce,
                    post_id: scriptdetective.post_id,
                    type: scriptType,
                    script: scriptId,
                    version: scriptVersion,
                    action_type: isEnabled ? 'enable' : 'disable'
                },
                success: (response) => {
                    if (response.success) {
                        $item.toggleClass('disabled', !isEnabled);
                    }
                },
                error: () => {
                    $input.prop('checked', !isEnabled);
                },
                complete: () => {
                    $item.removeClass('updating');
                }
            });
        },

        filterScripts: function() {
            const activeFilters = this.$filter.find('.filter-type:checked').map((i, el) => el.value).get();
            $('.script-item').each((i, el) => {
                const type = $(el).data('script-type');
                $(el).toggle(activeFilters.includes(type));
            });
        },

        getNoScriptsTemplate: function() {
            return `<div class="notice notice-info"><p>${scriptdetective.labels.no_scripts}</p></div>`;
        },

        showError: function(message) {
            this.$results.html(`<div class="notice notice-error"><p>${message}</p></div>`);
        },

        applyFilters: function() {
            this.$filter = this.$metabox.find('.scriptdetective-filter');
            this.filterScripts();
        }
    };

    ScriptDetective.init();
});