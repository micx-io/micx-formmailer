<?php

namespace Micx\FormMailer\Config;

class T_Preset
{
    /**
     * E-Mail Address to which the form is sent.
     *
     * @var string|null
     */
    public ?string $mail_to = null;

    /**
     * List of allowed mailto addresses. (Can be specified in "Mailto" field of the form)
     * Wildcards are supported *@domain.com, but not regex.
     *
     * *@@ -> Allow Mailto any address within mail_to domain
     *
     * @var string[]|null
     */
    public ?array $allow_mailto = [];

    /**
     * @var string|null
     */
    public ?string $template_url = null;


    /**
     * Paramter is a email address (like xy@abc.de). The input is not validated, so it can be any string.
     *
     * @param string $mailto
     * @return bool
     */
    public function checkMailto(string $mailto): bool
    {
        $mailto = strtolower($mailto);

        if ($mailto === $this->mail_to) {
            return true; // Exact match
        }


        $testMailToDomain = explode('@', $mailto, 2)[1] ?? null;
        $mailToDomain = explode('@', $this->mail_to ?? "", 2)[1] ?? null;
        if ($testMailToDomain === null || $testMailToDomain === '') {
            throw new \InvalidArgumentException("Invalid mailto format: '$mailto' or preset mail_to: '{$this->mail_to}'");
        }

        if (is_array ($this->allow_mailto) && in_array("*@@", $this->allow_mailto) && $testMailToDomain === $mailToDomain) {

            return true; // Allow any address within the same domain
        }

        if ($this->allow_mailto !== null) {
            foreach ($this->allow_mailto as $allowed) {
                if ($allowed === "*@@") {
                    continue; // Skip this, already handled above
                }
                if (fnmatch(strtolower($allowed), $mailto)) {

                    return true; // Allowed
                }
            }
        }

        throw new \InvalidArgumentException("Mailto '$mailto' is not allowed by template configuration. Allowed: " . implode(", ", $this->allow_mailto ?? []));
    }

}
