<?php

namespace Internal\Qossmic\Deptrac\Supportive\OutputFormatter;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInput;
use Qossmic\Deptrac\Contract\Result\Allowed;
use Qossmic\Deptrac\Contract\Result\LegacyResult;
use Qossmic\Deptrac\Contract\Result\Uncovered;
use Qossmic\Deptrac\Contract\Result\Violation;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\FileOccurrence;
use Qossmic\Deptrac\Core\Dependency\Dependency;
use Qossmic\Deptrac\Supportive\Console\Symfony\Style;
use Qossmic\Deptrac\Supportive\Console\Symfony\SymfonyOutput;
use Qossmic\Deptrac\Supportive\OutputFormatter\Configuration\FormatterConfiguration;
use Qossmic\Deptrac\Supportive\OutputFormatter\MermaidJSOutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class MermaidJSOutputFormatterTest extends TestCase
{
    /**
     * @dataProvider dataForTestFinish
     */
    public function testFinish(string $expected): void
    {
        $fileOccurrenceA = FileOccurrence::fromFilepath('classA.php', 0);
        $classA = ClassLikeToken::fromFQCN('ClassA');

        $context = new LegacyResult([
            new Violation(new Dependency($classA, ClassLikeToken::fromFQCN('ClassB'), $fileOccurrenceA), 'LayerA', 'LayerB'),
            new Violation(new Dependency($classA, ClassLikeToken::fromFQCN('ClassHidden'), $fileOccurrenceA), 'LayerA', 'LayerHidden'),
            new Violation(new Dependency(ClassLikeToken::fromFQCN('ClassAB'), ClassLikeToken::fromFQCN('ClassBA'), FileOccurrence::fromFilepath('classAB.php', 1)), 'LayerA', 'LayerB'),
            new Allowed(new Dependency($classA, ClassLikeToken::fromFQCN('ClassC'), $fileOccurrenceA), 'LayerA', 'LayerC'),
            new Uncovered(new Dependency($classA, ClassLikeToken::fromFQCN('ClassD'), $fileOccurrenceA), 'LayerC'),
        ], [], []);

        $bufferedOutput = new BufferedOutput();

        $output = $this->createSymfonyOutput($bufferedOutput);
        $outputFormatterInput = $this->createMock(OutputFormatterInput::class);

        $mermaidJSOutputFormatter = new MermaidJSOutputFormatter(new FormatterConfiguration([
            'mermaidjs' => [
                'direction' => 'TD',
                'groups' => [
                    'User' => [
                        'User Frontend',
                        'User Backend',
                    ],
                    'Admin' => [
                        'Admin',
                        'Admin Backend',
                    ],
                ],
            ],
        ]));
        $mermaidJSOutputFormatter->finish($context, $output, $outputFormatterInput);
        $this->assertSame($expected, $bufferedOutput->fetch());
    }

    public function dataForTestFinish(): iterable
    {
        yield [
            'expected' => file_get_contents(__DIR__.'/data/mermaidjs-expected.txt'),
        ];
    }

    private function createSymfonyOutput(BufferedOutput $bufferedOutput): SymfonyOutput
    {
        return new SymfonyOutput(
            $bufferedOutput,
            new Style(new SymfonyStyle($this->createMock(InputInterface::class), $bufferedOutput))
        );
    }
}
