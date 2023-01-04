<?php

declare(strict_types=1);

namespace Kynx\Mezzio\OpenApiGenerator\Console;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use finfo;
use Kynx\Mezzio\OpenApiGenerator\Model\ModelWriterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function file_exists;

use const FILEINFO_MIME_TYPE;
use const FILEINFO_NONE;

/**
 * @internal
 *
 * @see \KynxTest\Mezzio\OpenApiGenerator\Console\GenerateCommandTest
 *
 * @psalm-internal \Kynx\Mezzio\OpenApiGenerator
 * @psalm-internal \KynxTest\Mezzio\OpenApiGenerator
 */
#[AsCommand("generate")]
final class GenerateCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $openApiFile,
        private readonly ModelWriterInterface $modelWriter
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();

        $this->setDescription("Generate Mezzio application from OpenAPI specification");

        $mode = $this->openApiFile === '' ? InputArgument::REQUIRED : InputArgument::OPTIONAL;
        $this->addArgument(
            'specification',
            $mode,
            "Path to OpenAPI specification",
            $this->openApiFile ?: null
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $specification */
        $specification = $input->getArgument('specification');
        $specFile      = $this->projectDir . '/' . $specification;

        $openApi = $this->readSpecification($specFile, $output);
        if ($openApi === null) {
            return 1;
        }

        $this->modelWriter->write($openApi);

        return 0;
    }

    private function readSpecification(string $specFile, OutputInterface $output): OpenApi|null
    {
        if (! file_exists($specFile)) {
            $output->writeln("<error>Specification file '$specFile' does not exist.</error>");
            return null;
        }

        $finfo    = new finfo(FILEINFO_NONE);
        $mimeType = $finfo->file($specFile, FILEINFO_MIME_TYPE);

        try {
            if ($mimeType === 'application/json') {
                $openApi = Reader::readFromJsonFile($specFile);
            } else {
                $openApi = Reader::readFromYamlFile($specFile);
            }
        } catch (Throwable $e) {
            $output->writeln("<error>Error reading '$specFile': " . $e->getMessage() . "</error>");
            return null;
        }

        return $openApi;
    }
}
