<?php

namespace Yab\Watts\Generators;

use Illuminate\Filesystem\Filesystem;

trait FileMakerTrait
{
    public function copyPreparedFiles($directory, $destination)
    {
        $fileSystem = new Filesystem;

        $files = $fileSystem->allFiles($directory);

        $fileDeployed = false;

        $fileSystem->copyDirectory($directory, $destination);

        foreach ($files as $file) {
            $fileContents = $fileSystem->get($file);
            $fileContentsPrepared = str_replace('{{App\}}', 'App\\', $fileContents);
            $fileDeployed = $fileSystem->put($destination.'/'.$file->getRelativePathname(), $fileContentsPrepared);
        }

        return $fileDeployed;
    }

}
