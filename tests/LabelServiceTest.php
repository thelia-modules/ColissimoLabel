<?php

namespace ColissimoLabel\Tests;

use ColissimoLabel\Service\LabelService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LabelServiceTest extends TestCase
{
    public static function emptyTrackingNumbers(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => ['  '];
    }

    /**
     * An empty tracking number would select every file of the label folder for deletion.
     */
    #[DataProvider('emptyTrackingNumbers')]
    public function testDeleteLabelRefusesAnEmptyTrackingNumber(string $trackingNumber): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LabelService(new EventDispatcher()))->deleteLabel($trackingNumber);
    }

    /**
     * Deleting label 6A1 must not remove the files of label 6A12, which shares its prefix.
     */
    public function testDeleteLabelFileKeepsTheFilesOfAnotherNumberSharingThePrefix(): void
    {
        $folder = sys_get_temp_dir().'/colissimo-label-test-'.uniqid();
        mkdir($folder);

        try {
            foreach (['6A1.pdf', '6A1-CN23.pdf', '6A12.pdf', '6A12-CN23.pdf'] as $name) {
                touch($folder.'/'.$name);
            }

            $service = new class(new EventDispatcher()) extends LabelService {
                public function deleteIn(string $number, string $folder): void
                {
                    $this->deleteLabelFile($number, $folder);
                }
            };
            $service->deleteIn('6A1', $folder);

            $this->assertFileDoesNotExist($folder.'/6A1.pdf');
            $this->assertFileDoesNotExist($folder.'/6A1-CN23.pdf');
            $this->assertFileExists($folder.'/6A12.pdf');
            $this->assertFileExists($folder.'/6A12-CN23.pdf');
        } finally {
            foreach (glob($folder.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($folder);
        }
    }

    /**
     * A match in a subfolder (bordereau/) must not delete the file of the same name at the root.
     */
    public function testDeleteLabelFileOnlyLooksAtTheRootOfTheFolder(): void
    {
        $folder = sys_get_temp_dir().'/colissimo-label-test-'.uniqid();
        mkdir($folder.'/sub', 0777, true);

        try {
            touch($folder.'/sub/6A1.pdf');
            touch($folder.'/6A1.pdf');

            $service = new class(new EventDispatcher()) extends LabelService {
                public function deleteIn(string $number, string $folder): void
                {
                    $this->deleteLabelFile($number, $folder);
                }
            };
            $service->deleteIn('6A1', $folder);

            $this->assertFileDoesNotExist($folder.'/6A1.pdf');
            $this->assertFileExists($folder.'/sub/6A1.pdf');
        } finally {
            @unlink($folder.'/sub/6A1.pdf');
            @unlink($folder.'/6A1.pdf');
            @rmdir($folder.'/sub');
            @rmdir($folder);
        }
    }
}
