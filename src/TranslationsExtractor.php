<?php
declare(strict_types=1);

namespace Simplex;

use Simplex\Erp\ControllerAbstract;

/*
* Class to extract translations from templates
* @param string $operation
* @param string $context: share ! local if not specified both are included
*/
class TranslationsExtractor extends ControllerAbstract
{
    public function extractTranslations(string $operation, string $context = null)
    {
        switch ($operation) {
            //generate a clean pot file from both share and local
            case 'create':
                //Twig cache
                $this->generateTemplatesCaches($context);
                //generate pot file
                $this->generateStartingPotFile();
            break;
            //generate po files for each language to update translations
            case 'update':
                //Twig cache
                $this->generateTemplatesCaches($context);
                //generate pot file
                $this->generateStartingPotFile();
                //generate pot file
                $this->generateUpdatedPoFile($context);
            break;
        }
        /*
        * update share
            aggioranre i po in share
        */
        
        
    }
    
    /**
    * Builds the path to the translations cache
    **/
    private function buildPathToTranslationsCache()
    {
        return sprintf('%s/translations', TMP_DIR);
    }

    /**
    * Builds the path to a context template cache
    * @param string $context
    **/
    private function buildPathToContextTemplatesCache(string $context)
    {
        return sprintf('%s/cache/%s', $this->buildPathToTranslationsCache(), $context);
    }
    
    /**
    * Generate templates cache for both share and local templates
    * @param string $context: share | local
    **/
    private function generateTemplatesCaches(string $context)
    {
        //build helpers
        $this->buildTemplateHelpersBack();
        //set templates namespaces
        $loader = $this->template->getLoader();
        $loader->addPath(SHARE_TEMPLATES_DIR, '__main__');
        $loader->addPath(SHARE_TEMPLATES_DIR, 'share');
        $loader->addPath(LOCAL_TEMPLATES_DIR, '__main__');
        $loader->addPath(LOCAL_TEMPLATES_DIR, 'local');
        $this->template->setLoader($loader);
        //clean cache
        $pathToTemplatesCache = $this->buildPathToTranslationsCache();
        if(is_dir($pathToTemplatesCache)) {
            $command = sprintf('rm -rf %s', $pathToTemplatesCache);
            exec($command);
        }
        //generate cache
        //share cache is generated anywa
        $this->generateTemplatesCache('share', SHARE_TEMPLATES_DIR);
        //local cache is generated only in local context
        if($context == 'local') {
            $this->generateTemplatesCache('local', LOCAL_TEMPLATES_DIR);
        }
    }
    
    /**
    * Generate templates cache for both share and local templates
    * @param string $context: share | local
    * @param string $pathToTemplatesFolder
    **/
    private function generateTemplatesCache(string $context, string $pathToTemplatesFolder)
    {
        echo 'GENERATING TEMPLATES CACHE...' . PHP_EOL;
        //build cache
        $pathToContextTemplatesCache = $this->buildPathToContextTemplatesCache($context);
        $this->template->setCache($pathToContextTemplatesCache);
        $this->template->enableAutoReload();
        #$twig->addExtension(new Twig_Extensions_Extension_I18n());
        // iterate over all your templates
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathToTemplatesFolder), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            // force compilation
            if ($file->isFile() && $file->getExtension() == 'twig') {
                echo sprintf('%s/%s%s', $file->getPath(), $file->getFilename(), PHP_EOL);
                $template = $this->template->loadTemplate(str_replace($pathToTemplatesFolder.'/', '', $file));
            }
        }
        echo PHP_EOL;
    }
    
    /**
    * Generate .pot file to start a new translation
    **/
    private function generateStartingPotFile()
    {
        $domain = 'simplex';
        $package = 'Simplex';
        $pathToCacheFolder = $this->buildPathToTranslationsCache();
        $pathToFile = sprintf('%s/%s', $pathToCacheFolder, $domain);
        echo 'GENERATING STARTING POT FILE...' . PHP_EOL;
        $command = <<<EOT
find {$this->buildPathToTranslationsCache()} -type f \( -name '*.php' \) -print | xargs xgettext -c --default-domain={$domain} -p {$pathToCacheFolder} --from-code=UTF-8 --no-location  -L PHP -d {$domain} --package-name={$package} - && mv {$pathToFile}.po {$pathToFile}.pot
EOT;
        passthru($command, $output);
        if($output == 0) {
            echo sprintf('POT FILE GENERATED AS %s.pot%s', $pathToFile, PHP_EOL);
        } else {
            echo sprintf('ERROR GENERATING POT FILE FOR DOMAIN %%s', $domain, PHP_EOL);
        }
    }
    
    /**
    * Generate updated .po file for each language
    * @param string $context: share | local
    **/
    private function generateUpdatedPoFile(string $context)
    {
        //load configured languages
        $this->loadLanguages();
        foreach ($this->languages as $languageCode => $language) {
            $this->generateUpdatedLanguagePoFile($context, $language);
        }
    }
    
    /**
    * Generate updated .po file for each language
    * @param object $language
    **/
    private function generateUpdatedLanguagePoFileBAK(object $language)
    {
        $languageIETF = sprintf('%s_%s', $language->{'ISO-639-1'}, $language->{'ISO-3166-1-2'});
        echo sprintf('GENERATING UPDATED PO FILE FOR %s...%s', $languageIETF, PHP_EOL);
        $domain = 'simplex';
        $package = 'Simplex';
        $pathToPoFolder = sprintf('%s/%s/LC_MESSAGES', LOCALES_DIR, $languageIETF);
        $pathToCacheFolder = $this->buildPathToTranslationsCache();
        $pathToPotFile = sprintf('%s/%s.pot', $pathToCacheFolder, $domain);
        $pathToPoFile = sprintf('%s/%s.po', $pathToPoFolder, $domain);
        $command = <<<EOT
find {$this->buildPathToTranslationsCache()} -type f \( -name '*.php' \) -print | xargs xgettext -c --default-domain={$domain} -p {$pathToPoFolder} --from-code=UTF-8 --no-location -L PHP -d {$domain} --package-name={$package} -j - && msgattrib --set-obsolete --ignore-file={$pathToPotFile} -o {$pathToPoFile} {$pathToPoFile}
EOT;
        passthru($command, $output);
        if($output == 0) {
            echo sprintf('PO FILE GENERATED AS %s%s', $pathToPoFile, PHP_EOL);
        } else {
            echo sprintf('ERROR GENERATING POT FILE FOR LANGUAGE %%s', $languageIETF, PHP_EOL);
        }
    }
    /**
    * Generate updated .po file for each language
    * update local
    * - cache totale
    * - 
    * @param string $context: share | local
    * @param object $language
    **/
    private function generateUpdatedLanguagePoFile(string $context, object $language)
    {
        $languageIETF = sprintf('%s_%s', $language->{'ISO-639-1'}, $language->{'ISO-3166-1-2'});
        echo sprintf('GENERATING %s UPDATED PO FILE FOR %s...%s', $context, $languageIETF, PHP_EOL);
        $domain = 'simplex';
        $package = 'Simplex';
        //destination po file location changes by context
        $pathToShareLocalesRoot = sprintf('%s/../locales', PRIVATE_SHARE_DIR);
        $pathToLocalLocalesRoot = LOCALES_DIR;
        $pathToSharePoFolder = sprintf('%s/%s/LC_MESSAGES', $pathToShareLocalesRoot, $languageIETF);
        $pathToLocalPoFolder = sprintf('%s/%s/LC_MESSAGES', $pathToLocalLocalesRoot, $languageIETF);
        $pathToSharePoFile = sprintf('%s/%s.po', $pathToSharePoFolder, $domain);
        $pathToLocalPoFile = sprintf('%s/%s.po', $pathToLocalPoFolder, $domain);
        $pathToLocalMoFile = sprintf('%s/%s.mo', $pathToLocalPoFolder, $domain);
        $paths = [
            'share' => (object) [
                'localesRoot' => $pathToShareLocalesRoot,
                'poFolder' => $pathToSharePoFolder,
                'poFile' => $pathToSharePoFile
            ],
            'local' => (object) [
                'localesRoot' => $pathToLocalLocalesRoot,
                'poFolder' => $pathToLocalPoFolder,
                'poFile' => $pathToLocalPoFile
            ]
        ];
        $pathToCacheFolder = $this->buildPathToTranslationsCache();
        $pathToPotFile = sprintf('%s/%s.pot', $pathToCacheFolder, $domain);
        $commands = <<<EOT
find {$this->buildPathToTranslationsCache()} -type f \( -name '*.php' \) -print | xargs xgettext -c --default-domain={$domain} -p {$paths[$context]->poFolder} --from-code=UTF-8 --no-location -L PHP -d {$domain} --package-name={$package} -j - && msgattrib --set-obsolete --ignore-file={$pathToPotFile} -o {$paths[$context]->poFile} {$paths[$context]->poFile}
EOT;
        //if local context cat the share po file and regenerate the mo file to incorporate any new share translation
        if($context == 'local') {
            $commands .= <<<EOT
            && msgcat --use-first -o {$pathToLocalPoFile} {$pathToSharePoFile} {$pathToLocalPoFile} && msgfmt -o {$pathToLocalMoFile} {$pathToLocalPoFile}
EOT;
        }
        //exec commands
        passthru($commands, $output);
        if($output == 0) {
            echo sprintf('PO FILE GENERATED AS %s%s', $paths[$context]->poFile, PHP_EOL);
        } else {
            echo sprintf('ERROR GENERATING POT FILE FOR LANGUAGE %%s', $languageIETF, PHP_EOL);
        }
    }
}
