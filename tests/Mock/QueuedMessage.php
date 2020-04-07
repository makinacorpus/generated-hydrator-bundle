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

namespace GeneratedHydrator\Bridge\Symfony\Tests\Mock;

use Ramsey\Uuid\UuidInterface;

/**
 * This is a real class from a real project. We want to be sure everything
 * in this will be correctly parsed, and will not wake up the docblock parser
 * or the property-info component.
 */
final class QueuedMessage
{
    /** @var UuidInterface */
    private $id;

    /** @var \DateTimeInterface */
    private $created_at;

    /** @var ?\DateTimeInterface */
    private $consumed_at;

    /** @var bool */
    private $has_failed = false;

    /** @var string */
    private $body = '';

    /** @var string */
    private $queue = 'default';

    /** @var ?string */
    private $type;

    /** @var ?string */
    private $content_type;

    /** @var array<string, string> */
    private $headers = [];

    /** @var int */
    private $serial;
}
