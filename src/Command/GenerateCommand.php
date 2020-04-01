<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

declare(strict_types=1);

namespace GeneratedHydrator\Bridge\Symfony\Command;

use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
final class GenerateCommand extends Command
{
    protected static $defaultName = 'generated-hydrator:generate';

    /** @var DefaultHydrator */
    private $hydrator;

    /** @var string[] */
    private $classList;

    /**
     * Default constructor
     */
    public function __construct(DefaultHydrator $hydrator, array $classList)
    {
        parent::__construct();

        $this->hydrator = $hydrator;
        $this->classList = $classList;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription("Generate hydrators")
            // ->addArgument('class', InputArgument::REQUIRED,  "Action à faire: orphans")
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($this->classList)) {
            $output->writeln("<error>Class list to pre-generate is empty, please set the 'generated_hydrator.class_list' config option with PHP fully qualified class names</error>");
            return -1;
        }

        foreach ($this->classList as $className) {
            if (!\class_exists($className)) {
                $output->writeln("<error>Class '".$className."' does not exists, skipping.</error>");
                continue;
            }

            $written = $this->hydrator->regenerateHydrator($className);
            $output->writeln($className." -> ".$written['class']." in ".$written['filename']);
        }

        return 0;
    }
}
