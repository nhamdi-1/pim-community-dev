<?php

namespace Pim\Bundle\EnrichBundle\MassEditAction;

use Akeneo\Bundle\BatchBundle\Launcher\SimpleJobLauncher;
use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Pim\Bundle\EnrichBundle\MassEditAction\Operation\BatchableOperationInterface;
use Pim\Bundle\EnrichBundle\MassEditAction\Operation\ConfigurableOperationInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * Background process launcher for mass edit Operations.
 * It internally uses the Akeneo\Bundle\BatchBundle\Launcher\SimpleJobLauncher.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class OperationJobLauncher
{
    /** @var SimpleJobLauncher */
    protected $jobLauncher;

    /** @var IdentifiableObjectRepositoryInterface */
    protected $jobInstanceRepo;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param SimpleJobLauncher                     $jobLauncher
     * @param IdentifiableObjectRepositoryInterface $jobInstanceRepo
     * @param TokenStorageInterface                 $tokenStorage
     */
    public function __construct(
        SimpleJobLauncher $jobLauncher,
        IdentifiableObjectRepositoryInterface $jobInstanceRepo,
        TokenStorageInterface $tokenStorage
    ) {
        $this->jobLauncher = $jobLauncher;
        $this->jobInstanceRepo = $jobInstanceRepo;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Launch the background process for the given $operation
     *
     * @param BatchableOperationInterface $operation
     *
     * @throws NotFoundResourceException
     */
    public function launch(BatchableOperationInterface $operation)
    {
        $jobInstanceCode = $operation->getJobInstanceCode();
        $jobInstance = $this->jobInstanceRepo->findOneByIdentifier($jobInstanceCode);

        if (null === $jobInstance) {
            throw new NotFoundResourceException(sprintf('No JobInstance found with code "%s"', $jobInstanceCode));
        }

        $configuration = $operation->getBatchConfig();

        $this->jobLauncher->launch(
            $jobInstance,
            $this->tokenStorage->getToken()->getUser(),
            $configuration
        );
    }
}
