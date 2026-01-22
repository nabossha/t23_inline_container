<?php

declare(strict_types=1);

namespace Team23\T23InlineContainer\Xclass;

/*
 * This file is part of TYPO3 CMS-based extension "t23_inline_container" by TEAM23.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook as OriginalCommandMapPostProcessingHook;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * XCLASS for container's CommandMapPostProcessingHook
 *
 * Fixes duplicate children issue when using inline fields for container children.
 * When TYPO3's inline mechanism copies children, this hook detects they already
 * exist and skips container's copy to prevent duplicates.
 *
 * @see https://github.com/b13/container/issues/557
 */
class CommandMapPostProcessingHook extends OriginalCommandMapPostProcessingHook
{
    /**
     * Override copyOrMoveChildren to check if children were already copied by inline mechanism
     *
     * @param int $origUid Original container UID
     * @param int $newId Target position/page
     * @param int $containerId New container UID (after copy)
     * @param string $command 'copy' or 'move'
     * @param DataHandler $dataHandler
     */
    protected function copyOrMoveChildren(int $origUid, int $newId, int $containerId, string $command, DataHandler $dataHandler): void
    {
        // FIX: Check if children were already copied by TYPO3's inline mechanism
        // When using t23-inline-container, the inline field causes TYPO3 to copy children
        // automatically. We detect this and skip container's copy to prevent duplicates.
        if ($command === 'copy' && $containerId > 0) {
            try {
                $newContainer = $this->containerFactory->buildContainer($containerId);
                $existingChildren = $newContainer->getChildRecords();
                if (!empty($existingChildren)) {
                    // Children already exist (copied by inline mechanism), skip to prevent duplicates
                    return;
                }
            } catch (\B13\Container\Domain\Factory\Exception $e) {
                // Container not found or not a container, continue with parent implementation
            }
        }

        // Call parent implementation for normal behavior
        parent::copyOrMoveChildren($origUid, $newId, $containerId, $command, $dataHandler);
    }
}
