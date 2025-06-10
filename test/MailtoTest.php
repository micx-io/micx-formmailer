<?php

namespace test;

use Micx\FormMailer\Config\T_Preset;
use PHPUnit\Framework\TestCase;

class MailtoTest extends TestCase
{

    public function preset(array $args = []): T_Preset
    {
        $preset = new T_Preset();
        foreach ($args as $k => $v) {
            $preset->$k = $v;
        }
        return $preset;
    }

    public function testExactMatch()
    {
        $preset = $this->preset([
            'mail_to' => 'user@example.com',
            'allow_mailto' => []
        ]);
        $this->assertTrue($preset->checkMailto('user@example.com'));
        // Should be case-insensitive
        $this->assertTrue($preset->checkMailto('USER@EXAMPLE.COM'));
    }

    public function testAllowMailtoWildcardDomainAtDomain()
    {
        $preset = $this->preset([
            'mail_to' => 'user@domain.com',
            'allow_mailto' => ['*@domain.com']
        ]);
        $this->assertTrue($preset->checkMailto('someone@domain.com'));
        $this->assertTrue($preset->checkMailto('foo.bar@domain.com'));
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('user@other.com');
    }

    public function testAllowMailtoMultipleWildcards()
    {
        $preset = $this->preset([
            'mail_to' => 'main@multi.com',
            'allow_mailto' => ['*@multi.com', 'special@other.com']
        ]);
        $this->assertTrue($preset->checkMailto('anything@multi.com'));
        $this->assertTrue($preset->checkMailto('special@other.com'));
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('nope@none.com');
    }

    public function testAllowMailtoAnyWithinSameDomainByAtAt()
    {
        $preset = $this->preset([
            'mail_to' => 'admin@mydomain.org',
            'allow_mailto' => ['*@@'],
        ]);
        // Should extract domain from mail_to (mydomain.org)
        $this->assertTrue($preset->checkMailto('foo@mydomain.org'));
        $this->assertTrue($preset->checkMailto('bar@mydomain.org'));
        // Should not allow other domains
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('baz@other.org');
    }

    public function testAllowMailtoWithFnmatch()
    {
        $preset = $this->preset([
            'mail_to' => 'main@wildcard.com',
            'allow_mailto' => ['special?@wildcard.com'],
        ]);
        $this->assertTrue($preset->checkMailto('special1@wildcard.com'));
        $this->assertTrue($preset->checkMailto('speciala@wildcard.com'));
        // Not matching
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('special12@wildcard.com');
    }

    public function testAllowMailtoNullOrEmpty()
    {
        // Null allow_mailto (should only accept exact match)
        $preset = $this->preset([
            'mail_to' => 'x@a.com',
            'allow_mailto' => null
        ]);
        $this->assertTrue($preset->checkMailto('x@a.com'));
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('y@a.com');
    }

    public function testMailtoNoAllowMatchThrowsException()
    {
        $preset = $this->preset([
            'mail_to' => 'person@company.com',
            'allow_mailto' => ['abc@d.com', '*@example.com']
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('nobody@company.com');
    }

    public function testMailtoBrokenMailtoString()
    {
        // Even if mailto is technically invalid, it attempts fnmatch/string checks (not validation)
        $preset = $this->preset([
            'mail_to' => 'x@a.com',
            'allow_mailto' => ['*@@'],
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('notanemail');
    }

    public function testMailtoWildcardAsterisk()
    {
        $preset = $this->preset([
            'mail_to' => 'main@x.com',
            'allow_mailto' => ['*']
        ]);
        $this->assertTrue($preset->checkMailto('anyone@any.com'));
        $this->assertTrue($preset->checkMailto('weirdstring.+@random.tld'));
    }

    public function testMailtoAllowMailtoEmptyArray()
    {
        $preset = $this->preset([
            'mail_to' => 'me@a.com',
            'allow_mailto' => []
        ]);
        $this->assertTrue($preset->checkMailto('me@a.com'));
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('notme@a.com');
    }

    public function testMailtoMultipleAtAtAndWildcard()
    {
        $preset = $this->preset([
            'mail_to' => 'main@x.com',
            'allow_mailto' => ['*@@', '*@other.com']
        ]);
        $this->assertTrue($preset->checkMailto('foo@x.com'));
        $this->assertTrue($preset->checkMailto('bar@other.com'));
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('baz@diff.com');
    }

    public function testMailtoNullMailto()
    {
        // If mail_to is null, should only match if $mailto is also null
        $preset = $this->preset([
            'mail_to' => null,
            'allow_mailto' => ['*@null.com']
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $preset->checkMailto('any@domain.com'); // strtolower(null) will fail
    }

}
