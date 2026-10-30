<?php
// Jana semula storage/lang/*.php dari lang/*.php + jadual translations (B14). Dipanggil SELEPAS commit oleh job yang
// menyimpan terjemahan (SaveTranslationJob, SaveGradeJob, SaveModuleJob) dan oleh tools/generate-lang.php (deploy).
class GenerateLangFilesJob
{
    // [ kod => bilangan nilai owner ]
    public function run()
    {
        return LangFiles::generate();
    }
}
