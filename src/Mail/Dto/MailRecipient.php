<?php

namespace izi\prestashop\Mail\Dto;

class MailRecipient
{
    /**
     * @var string[]
     */
    private $emails;

    /**
     * @var string[]
     */
    private $bcc;

    /**
     * @param string[] $email
     * @param string[] $bcc
     */
    public function __construct(array $email, array $bcc = [])
    {
        $this->bcc = $bcc;
        $this->emails = $email;
    }

    /**
     * @return string[]
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * @return string[]
     */
    public function getEmails(): array
    {
        return $this->emails;
    }
}
