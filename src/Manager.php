<?php

namespace Themsaid\LangmanGUI;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Manager
{
    /**
     * The Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $disk;

    /**
     * Path to the language files.
     *
     * @var string
     */
    private $languageFilesPath;

    /**
     * Paths we will look inside to find translations.
     *
     * @var array
     */
    private $lookupPaths;

    /**
     * Available translations.
     *
     * @var array
     */
    private $translations = [];

    /**
     * Manager constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $disk
     * @param string $languageFilesPath
     * @param array $lookupPaths
     */
    public function __construct(Filesystem $disk, $languageFilesPath, array $lookupPaths)
    {
        $this->disk = $disk;
        $this->languageFilesPath = $languageFilesPath;
        $this->lookupPaths = $lookupPaths;
    }

    /**
     * Get all the available lines.
     *
     * @param bool $reload
     * @return array
     */
    public function getTranslations($reload = false)
    {
        if ($this->translations && ! $reload) {
            return $this->translations;
        }

        if(!$this->translations) $this->addLanguage(config('langmanGUI.base_language'));

        $this->getJsonTranslations();
        $this->getArrayTranslations();

        return $this->translations;
    }

    /**
     * Synchronize the language keys from files.
     *
     * @return array
     */
    public function sync()
    {
        $this->backup();

        $output = [];

        $translations = $this->getTranslations();

        $keysFromFiles = array_collapse($this->getTranslationsFromFiles());

        foreach (array_unique($keysFromFiles) as $fileName => $key) {
            foreach ($translations as $lang => $keys) {
                if (! array_key_exists($key, $keys)) {
                    $output[] = $key;
                }
            }
        }

        return array_values(array_unique($output));
    }

    /**
     * Save the given translations.
     *
     * @param $translations
     */
    public function saveTranslations($translations)
    {
        $this->backup();

        foreach ($translations as $lang => $file) {

            foreach($file as $name => $lines) {

                if(is_array($lines)) ksort($lines);

                if(strpos($name, '.json') !== false) {
                    file_put_contents($this->languageFilesPath . DIRECTORY_SEPARATOR . "$name", json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }

                if(strpos($name, '.php') !== false) {
                    file_put_contents($this->languageFilesPath . DIRECTORY_SEPARATOR . "$lang" . DIRECTORY_SEPARATOR . "$name", "<?php\n\nreturn " . var_export($lines, true) . ";".\PHP_EOL);
                }

            }
            
        }
    }

    /**
     * Add a new JSON language file.
     *
     * @param $language
     */
    public function addLanguage($language)
    {
        $this->backup();

        if(!file_exists($this->languageFilesPath . DIRECTORY_SEPARATOR . "$language.json")) {
            file_put_contents($this->languageFilesPath . DIRECTORY_SEPARATOR . "$language.json",
                json_encode((object)['Hello World' => 'Hello World'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }

        if(!file_exists($this->languageFilesPath . DIRECTORY_SEPARATOR . "$language")) {
            $this->disk->makeDirectory($this->languageFilesPath . DIRECTORY_SEPARATOR . "$language");
        }   
    }

    /**
     * Get found translation lines found per file.
     *
     * @return array
     */
    private function getTranslationsFromFiles()
    {
        /*
         * This pattern is derived from Barryvdh\TranslationManager by Barry vd. Heuvel <barryvdh@gmail.com>
         *
         * https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
         */
        $functions = config('langmanGUI.localization_methods', ['__']);

        $pattern =
            // See https://regex101.com/r/jS5fX0/5
            '[^\w]' . // Must not start with any alphanum or _
            '(?<!->)' . // Must not start with ->
            '(' . implode('|', $functions) . ')' .// Must start with one of the functions
            "\(\s?" .// Match opening parentheses with optional space character
            "[\'\"]" .// Match " or '
            '(' .// Start a new group to match:
            '.+' .// Must start with group
            ')' .// Close group
            "[\'\"]?" .// Closing quote
            "[\s\),]"  // Close parentheses optional space character or new parameter
        ;

        $allMatches = [];

        foreach ($this->disk->allFiles($this->lookupPaths) as $file) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                $allMatches[$file->getRelativePathname()] = $matches[2];
            }
        }

        return $allMatches;
    }

    /**
     * Backup the existing translation files
     */
    private function backup()
    {
        if (! $this->disk->exists(storage_path('langmanGUI'))) {
            $this->disk->makeDirectory(storage_path('langmanGUI'));

            $this->disk->put(storage_path('langmanGUI'.'/.gitignore'), "*\n!.gitignore");
        }

        $this->disk->copyDirectory(resource_path('lang'), storage_path('langmanGUI/'.time()));
    }

    public function getJsonTranslations()
    {
        collect($this->disk->allFiles($this->languageFilesPath))
            ->filter(function ($file) {
                return $this->disk->extension($file) == 'json';
            })
            ->each(function ($file) {
                $translations = json_decode($file->getContents(), true);
                $this->addTranslations(str_replace('.json', '', $file->getFilename()), $file->getFilename(), $translations ?: []);
            });
    }

    public function getArrayTranslations()
    {
        collect($this->disk->allFiles($this->languageFilesPath))
            ->filter(function ($file) {
                return $this->disk->extension($file) == 'php';
            })
            ->each(function ($file) {
                $translations = $this->disk->getRequire($file->getPathname());
                $this->addTranslations($file->getRelativePath(), $file->getFilename(), $translations ?: []);
            });
    }

    /**
     * @param $language
     * @param $filename
     * @param array $translations
     */
    private function addTranslations($language, $filename, array $translations)
    {
        isset($this->translations[$language][$filename]) ? $this->translations[$language][$filename] += $translations : $this->translations[$language][$filename] = $translations;
    }
}
