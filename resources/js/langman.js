require('./bootstrap.js');

new Vue({
    el: '#app',

    data() {
        return {
            searchPhrase: '',
            searchCategory: '',
            baseLanguage: langman.baseLanguage,
            selectedLanguage: langman.baseLanguage,
            selectedFile: false,
            languages: langman.languages,
            files: Object.keys(langman.translations[langman.baseLanguage]),
            translations: langman.translations,
            selectedKey: null,
            hasChanges: false,
            textDirection: 'ltr',
        };
    },

    /**
     * The component has been created by Vue.
     */
    mounted() {
        this.addValuesToBaseLanguage();
    },

    computed: {

        /**
         * List of filtered translation keys.
         */
        filteredTranslations() {
            if (this.searchPhrase) {
                return _.chain(this.currentLanguageTranslations)
                    .pickBy(line => {
                        return String(line.key).toLowerCase().indexOf(this.searchPhrase.toLowerCase()) > -1 || String(line.value).toLowerCase().indexOf(this.searchPhrase.toLowerCase()) > -1;
                    })
                    .sortBy('value')
                    .value();
            }

            return _.sortBy(this.currentLanguageTranslations, 'value');
        },

        /**
         * List of filtered file names.
         */
        filteredFiles() {
            if (this.searchCategory) {
                return this.files.filter(file => {
                    file = this.stripExtension(file);
                    return file.toLowerCase().indexOf(this.searchCategory.toLowerCase()) > -1;
                });
            }

            return this.files;
        },


        /**
         * List of translation lines from the current language.
         */
        currentLanguageTranslations() {
            let translations = [];

            if(!this.selectedFile) {
                _.forEach(this.translations[this.selectedLanguage], (values, file) => {
                    _.forEach(values, (value, key) => {
                        translations.push({ key: key, value: value ? value : '', language: this.selectedLanguage, file: file });
                    })
                });
            } else {
                _.forEach(this.translations[this.selectedLanguage][this.selectedFile], (value, key) => {
                    translations.push({key: key, value: value ? value : '', language: this.selectedLanguage, file: this.selectedFile});
                });
            }

            return translations;
        },


        /**
         * List of untranslated keys from the current language.
         */
        currentLanguageUntranslatedKeys() {
            if(!this.selectedFile) {
                return;
            }
            return _.filter(this.translations[this.selectedLanguage][this.selectedFile], value => {
                return !value;
            });
        },

        selected() {
            if(this.selectedFile) {
                return this.translations[this.selectedLanguage][this.selectedFile][this.selectedKey];
            }
        }
    },


    methods: {
        /**
         * Add a new translation key.
         */
        promptToAddNewKey() {
            var key = prompt("Please enter the new key");

            if (key != null) {
                this.addNewKey(key);
            }
        },


        /**
         * Add a new translation key
         */
        addNewKey(key) {
            if (this.translations[this.baseLanguage][this.selectedFile][key] !== undefined) {
                return alert('This key already exists.');
            }

            _.forEach(this.languages, lang => {
                if (!this.translations[lang][this.selectedFile]) {
                    this.translations[lang][this.selectedFile] = {};
                }

                this.$set(this.translations[lang][this.selectedFile], key, '');
            });
        },


        /**
         * Remove the given key from all languages.
         */
        removeKey(key) {
            if (confirm('Are you sure you want to remove "' + key + '"')) {
                _.forEach(this.languages, lang => {
                    _.forEach(this.files, file => {
                        this.translations[lang][file] = _.omit(this.translations[lang][file], [key]);
                    });
                });

                this.selectedKey = null;
            }
        },


        /**
         * Add a new language file.
         */
        addLanguage() {
            var key = prompt("Enter language key (e.g \"en\")");

            this.languages.push(key);

            if (key != null) {
                $.ajax('/langman/add-language', {
                    data: JSON.stringify({language: key}),
                    headers: {"X-CSRF-TOKEN": langman.csrf},
                    type: 'POST', contentType: 'application/json'
                }).done(_ => {
                    this.languages.push(key);
                })
            }
        },


        /**
         * Save the translation lines.
         */
        save() {
            var self = this;
            $.ajax('/langman/save', {
                data: JSON.stringify({translations: this.translations}),
                headers: {"X-CSRF-TOKEN": langman.csrf},
                type: 'POST', contentType: 'application/json'
            }).done(function () {
                self.hasChanges = false;
                alert('Saved Successfully.');
            })
        },


        /**
         * Collect untranslated strings from project files.
         */
        scanForKeys() {
            $.post('/langman/scan', {_token: langman.csrf})
                .done(response => {
                    if (typeof response === 'object') {

                        console.log(response);
                        Object.assign(this.translations, response);

                        return alert('Langman searched your files & found new keys to translate.');
                    }

                    alert('No new keys were found.');
                })
        },

        /**
         * Add values to the base language used.
         */
        addValuesToBaseLanguage() {
            _.forEach(this.files, file => {
                _.forEach(this.translations[this.baseLanguage][file], (value, key) => {
                    if (!value) {
                        this.translations[this.baseLanguage][file][key] = key;
                    }
                });
            });            
        },

        /**
         * Add values to the base language used.
         */
        addValuesToBaseLanguage() {
            _.forEach(this.translations[this.baseLanguage], (value, key) => {
                if (!value) {
                    this.translations[this.baseLanguage][key] = key;
                }
            });
        },

        /**
         * Toggle direction of text between LTR and RTL
         */
        toggleTextDirection() {
            this.textDirection = this.textDirection === 'ltr' ? 'rtl' : 'ltr';

        },

        highlight(value) {
            if(typeof value !== 'string') return String(value);

            return value.replace(/:{1}[\w-]+/gi, function (match){return '<mark>' + match +'</mark>';});
        },

        stripExtension(value) {
            return value.substr(0, value.indexOf('.'));
        },

        selectFile(file) {
            this.selectedFile = file;
            this.searchCategory = '';
        }
    },

    watch: {
        translations:  {
            handler: function(translations) {
                this.hasChanges = true;
                this.files = Object.keys(translations[this.selectedLanguage]);
            },
            deep: true
        },

        hasChanges: function() {
            if(this.hasChanges) {
                window.onbeforeunload = function () {
                    return 'Are you sure you want to leave?';
                };
            }
            else {
                window.onbeforeunload = null;
            }
        },

        selectedLanguage: function(language) {
            this.files = Object.keys(this.translations[language]);
            this.selectedFile = this.files[0];
        },

        selectedKey: function(key)
        {
            this.selectedKey = String(key);
        },

        searchPhrase: function(phrase)
        {
            if(phrase) {
                this.selectedFile = false;
            }
        }
    }
});
