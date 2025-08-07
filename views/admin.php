<?php
/**
 * Admin View
 */

use MapWidget\Database;

$errorCount = 0;
$entries = Database::getAll();
foreach ($entries as $entry) {
    if ($entry->error) {
        $errorCount++;
    }
}

$categories = !empty($entries) ? array_values(array_unique(array_column($entries, 'category'))) : [];
if (!in_array('Agriculture', $categories, true)) {
    $categories[] = 'Agriculture';
}
sort($categories);

$googleApiKey = Database::getSetting('google_api_key');
$categoryColors = Database::getSetting('category_colors');

?>
<body id="app" class="mw-900 mx-auto p-2" @click="bodyClick(event)" v-cloak>

    <div class="row right mt-1">
        <a href="<?= ROOT_URI; ?>?page=logout">Logout</a>
    </div>

    <div v-if="importFile.error" class="border border-error rounded p-2 color-error mb-2">
        {{importFile.error}}
    </div>

    <div>
        <h4 class="mb-1 mt-3">Google API Key</h4>
        <form @submit.prevent="updateGoogleKey()">
            <div class="row">
                <input type="password" v-model="googleKey" />
                <button type="submit" class="btn w-200 loader" :class="{loading: googleKeyLoading}" :disabled="loading || googleKeyLoading || settingsLoading">Save</button>
            </div>
        </form>
    </div>

    <div>
        <h4 class="mb-1 mt-3">Upload New Entries</h4>
        <form enctype="multipart/form-data" @submit.prevent="runImportFile()">
            <div class="row">
                <input type="file" v-model="importFile.selected" required style="line-height: 34px;" :disabled="loading || googleKeyLoading || settingsLoading" />
                <select v-model="importFile.process" required :disabled="!importFile.selected || loading || googleKeyLoading || settingsLoading">
                    <option value="" disabled selected>- Select Action</option>
                    <option value="append">Append to Existing</option>
                    <option value="override">Delete All and Replace</option>
                </select>
                <button type="submit" class="btn w-200 loader" :class="{loading: loading}" :disabled="!importFile.selected || loading || googleKeyLoading || settingsLoading">Import</button>
            </div>
            <div class="col-12 mt-1 sm">
                <p>* File must be a .csv file and formatted like this <a style="color: #0077ee;" href="<?= ROOT_URI; ?>?page=download-example">example.csv</a></p>
                <p style="margin-top: 0">* Entries with the same Project, Address, City, State, and Zip will be updated and not added.</p>
            </div>  
        </form>
    </div>

    <div class="row columns-1 m-2 mt-4 g-0">
        <h5><span class="inline-block w-200">Entries (<span v-if="loading">{{entriesLoaded}}/</span><span>{{entriesTotal}}</span>)</span>
            <label class="ml-3 small" v-if="errorCount">
                <input type="checkbox" v-model="showErrors" style="margin-bottom: 2px;" /> <small>Show Errors ({{errorCount}})</small>
            </label>
        </h5>
        <div class="overflow-auto h-400 border border-light">
            <h6 v-if="!entries.length">Upload a File to start adding entries</h6>
            <table class="m-0">
                <thead>
                    <tr>
                        <td v-if="importFile.process === 'override'" class="pl-2 text-center" width="8%">
                            Row #
                        </td>
                        <td class="px-2 text-center">
                            Status
                        </td>
                        <td>
                           Information
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in entries">
                        <td v-if="(!showErrors || (showErrors && errorCount && entry.error)) && entry.rowNumber && importFile.process === 'override'" class="pt-1 center color-medium text-center" width="1%">
                            <small>{{entry.rowNumber}}</small>
                        </td>
                        <td v-if="(!showErrors || (showErrors && errorCount && entry.error)) && entry.rowNumber" class="pt-2 center text-center" width="24" style="height: 50px;">
                            <svg v-if="entry.error" class="color-error" viewBox="0 0 24 24" style="width: 18px; height: 18px;"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" /></svg>
                            <svg v-else-if="entry.success || (!entry.error && entry.latitude && entry.longitude)" class="color-success" viewBox="0 0 24 24" style="width: 18px; height: 18px;"><path fill="currentColor" d="M21 7 9 19l-5.5-5.5 1.41-1.41L9 16.17 19.59 5.59 21 7z" /></svg>
                            <div v-else-if="!entry.loaded" class="loader loading sm mr-1 accent-medium" style="margin-top: -2px;"></div>
                        </td>
                        <td v-if="(!showErrors || (showErrors && errorCount && entry.error)) && entry.rowNumber">
                            {{entry.project}}<br>
                            {{entry.program}}<br>
                            {{entry.address}} {{entry.city}}, {{entry.state}}<br>
                            {{entry.amount}}
                            <div v-if="entry.error" class="color-error">
                                {{entry.error}}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <a class="btn xs btn-outline" href="<?= ROOT_URI; ?>?page=download">Download Entries <svg viewBox="0 0 24 24" class="ml-1"><path fill="currentColor" d="M5 20h14v-2H5m14-9h-4V3H9v6H5l7 7 7-7z" /></svg></a>

    <div>
        <h3 class="mb-3 mt-4">Settings</h3>
        <form @submit.prevent="updateSettings()">
            <h4 class="mb-3 mt-2">Set Category Colors</h4>
            <div v-for="category in categories" class="row g-0 mb-2">
                <div>{{ category }}</div>
                <div>
                    <div class="row columns-auto hex-input">
                        <div class="col-auto btn btn-outline btn-icon">
                            #
                        </div>
                        <div>
                            <input type="text" v-model="categoryColors[category]" v-only-hex value="" maxlength="7" />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn w-200 loader mt-4" :class="{loading: settingsLoading}" :disabled="loading || googleKeyLoading || settingsLoading">Update</button>
        </form>
    </div>

    <div class="row columns-1 m-2 mt-4 g-0">
        <h4>Embed Iframe</h4>
        <h5>To add the map to another site, just copy and post this embed code on the website</h5>
        <textarea rows="5"><!-- Start PG MAP Embed -->
<div style="position: relative; overflow: hidden; width: 100%; height: 475px;"><iframe src="https://<?= $_SERVER['HTTP_HOST'].ROOT_URI; ?>?page=map" allowtransparency="true" style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"></iframe></div>
<!-- End PG MAP Embed --></textarea>
    </div>

    <div class="row columns-1 m-2 mt-4 g-0">
        <h5>Preview</h5>
        <div style="position: relative; overflow: hidden; width: 100%; height: 475px; margin-bottom: 4rem;">
            <iframe id="preview-iframe" src="https://<?= $_SERVER['HTTP_HOST'].ROOT_URI; ?>?page=map" allowtransparency="true" style="position: absolute; inset: 0; width: 100%; height: 100%; border: 1px solid #888;"></iframe>
        </div>
    </div>

    <div class="modal blur" :class="{show: modalError}">
        <div class="modal-content" style="max-width: 400px;">
            <div class="card card-light shadow color-text border rounded p-3">
                <button type="button" class="btn btn-link btn-close circle mr-1 mt-1 inset-top inset-right" @click="modalError = ''"></button>
                <h5 class="color-error">{{ modalError }}</h5>
            </div>
        </div>
    </div>

    <script type="module">
        var allEntries = JSON.parse('<?= json_encode($entries); ?>');
        import { createApp } from '<?= ASSETS_URI; ?>/petite-vue.module.min.js';

        const onlyHexDirective = (ctx) => {
            const handler = () => {
                const el = ctx.el;
                const stripped = el.value.replaceAll(/[^0-9a-fA-F]/g, '').toLowerCase().substr(0, 6);
                if (stripped !== el.value) {
                    el.value = stripped;
                    el.dispatchEvent(new Event('input'));
                }
            };

            ctx.el.addEventListener('input', handler);

            return () => {
                ctx.el.removeEventListener('input', handler);
            };
        };

        createApp({
            importFile: {
                error: '',
                selected: '',
                started: 0,
                process: ''
            },
            modalError: '',
            loading: false,
            errorCount: <?= intval($errorCount); ?>,
            showErrors: false,
            entries: JSON.parse(JSON.stringify(allEntries)),
            entriesTotal: <?= count($entries); ?>,
            entriesLoaded: 0,
            googleKey: '<?= $googleApiKey ? $googleApiKey : ''; ?>',
            googleKeyLoading: false,
            categories: JSON.parse('<?= json_encode($categories); ?>'),
            categoryColors: <?= !empty($categoryColors) ? json_decode(json_encode($categoryColors)) : '{}';?>,
            settingsLoading: false,

            runImportFile() {
                this.loading = true;
                this.importFile.error = '';
                this.importFile.started = 0;
                this.errorCount = 0;
                this.showErrors = false;
                
                if (this.importFile.process === 'override') {
                    this.entriesTotal = 0;
                    this.categories = [];
                    this.entries = [];
                }

                var inputFile = document.querySelector('input[type="file"]');

                if (!inputFile || ! inputFile.files[0]) {
                    this.loading = false;
                    this.importFile.error = 'Missing Import File';
                    return;
                }

                var formData = new FormData();
                formData.append('action', 'importFile');
                formData.append('file', inputFile.files[0]);
                formData.append('process', this.importFile.process);

                fetch('<?= ROOT_URI; ?>', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json()).then(response => {
                    if (response && response.entries) {
                        this.entries.forEach(entry => {
                            entry.loaded = true;
                        });
                        var newEntries = JSON.parse(JSON.stringify(response.entries));
                        newEntries.forEach(entry => {
                            entry.loaded = false;
                            if (this.hasEntry(entry) === false) {
                                this.entries.push(entry);
                                this.entriesTotal++;
                            }
                        });

                        allEntries = this.entries;

                        this.entries.forEach(entry => {
                            if (!entry.loaded) {
                                this.importFile.started++;
                                if (entry.category && !this.categories.includes(entry.category)) {
                                    this.categories.push(entry.category);
                                }
                                this.delayImportEntry(entry);
                                var entryData = JSON.parse(JSON.stringify(entry))
                                if (entryData && entryData.error && entryData.error.indexOf('Missing Google API Key') > -1) {
                                    return;
                                }
                            }
                        });

                        if (!this.importFile.started) {
                            setTimeout(() => {
                                this.checkIfLoading();
                            }, 1000);
                        }
                    } else {
                        this.importFile.error = response && response.error ? response.error : 'Unknown Error Processing file.';
                        this.loading = false;
                    }

                    return;
                }).catch(error => {
                    this.loading = false;
                });
            },

            hasEntry(entry) {
                for (let i = 0; i < this.entries.length; i++) {
                    if (this.entries[i].address === entry.address && this.entries[i].city === entry.city && this.entries[i].state === entry.state && this.entries[i].project === entry.project) {
                        return i;
                    }
                }
                return false;
            },

            delayImportEntry(entry) {
                setTimeout(() => {
                    this.runImportEntry(entry);
                }, (2000 * Math.round((this.importFile.started / 20))));
            },

            runImportEntry(entry) {
                entry.loading = true;
                entry.success = false;
                entry.error = '';

                var formData = new FormData();
                formData.append('action', 'importEntry');
                formData.append('entry', JSON.stringify(entry));

                fetch('<?= ROOT_URI; ?>', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json()).then(response => {
                    entry.loading = false;
                    entry.loaded = true;
                    entry.success = response && response.success;
                    entry.error = response && response.error ? response.error : false;
                    if (entry.error && entry.rowNumber > 0) {
                        this.errorCount++;
                    }
                    var existing = this.hasEntry(entry);
                    if (existing !== false) {
                        this.entries[existing] = entry;
                    }
                    this.checkIfLoading();
                    return;
                }).catch(error => {
                    entry.loading = false;
                    entry.loaded = true;
                    entry.error = 'Unknown Error Processing Entry.';
                    if (entry.rowNumber > 0) {
                        this.errorCount++;
                    }
                    this.checkIfLoading();
                });
            },

            checkIfLoading() {
                var isLoading = false;
                this.entriesLoaded = 0;
                this.entries.forEach(entry => {
                    if (!entry.loaded) {
                        isLoading = true;
                    } else {
                        this.entriesLoaded++;
                    }
                });
                this.loading = isLoading;

                if (this.entriesLoaded >= allEntries.length) {
                    setTimeout(() => {
                        document.getElementById('preview-iframe').contentWindow.location.reload();
                    }, 1000);
                }
            },

            updateGoogleKey() {
                this.googleKeyLoading = true;
                var formData = new FormData();
                formData.append('action', 'updateGoogleKey');
                formData.append('google_api_key', this.googleKey);

                fetch('<?= ROOT_URI; ?>', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json()).then(response => {
                    this.googleKeyLoading = false;
                    if (response && response.success) {
                        document.getElementById('preview-iframe').contentWindow.location.reload();
                    } else if (response && response.error) {
                        this.modalError = response.error;
                    }
                    return;
                }).catch(error => {
                    this.googleKeyLoading = false;
                    this.modalError = 'Unknown Error. Please try again';
                });
            },

            updateSettings() {
                this.settingsLoading = true;
                var formData = new FormData();
                formData.append('action', 'updateSettings');
                formData.append('category_colors', JSON.stringify(this.categoryColors));

                fetch('<?= ROOT_URI; ?>', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json()).then(response => {
                    this.settingsLoading = false;
                    if (response && response.success) {
                        document.getElementById('preview-iframe').contentWindow.location.reload();
                    } else if (response && response.error) {
                        this.modalError = response.error;
                    }
                    return;
                }).catch(error => {
                    this.settingsLoading = false;
                    this.modalError = 'Unknown Error. Please try again';
                });
            },

            bodyClick(event) {
                if (event.target.tagName === 'BUTTON' && event.target.classList.contains('btn')) {
                    event.target.blur();
                }
                document.querySelectorAll('label.toggle.open input').forEach(elem => {
                    if (elem !== event.target || (event.target.tagName === 'BUTTON' && event.target.classList.contains('btn'))) {
                        elem.checked = false;
                        elem.parentElement.classList.remove('open');
                    }
                });
            },

            toggleChanged(event) {
                if (event.target.checked) {
                    event.target.parentElement.classList.add('open');
                } else {
                    event.target.parentElement.classList.remove('open');
                }
            }
        }).directive('only-hex', onlyHexDirective).mount('#app');
    </script>
</body>
