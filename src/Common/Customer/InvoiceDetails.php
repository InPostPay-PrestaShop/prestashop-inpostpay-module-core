<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Customer;

final class InvoiceDetails implements \JsonSerializable
{
    /**
     * @var LegalForm
     */
    private $legal_form;

    /**
     * @var string
     */
    private $country_code;

    /**
     * @var string|null
     */
    private $tax_id_prefix;

    /**
     * @var string|null
     */
    private $tax_id;

    /**
     * @var string|null
     */
    private $company_name;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $surname;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $street;

    /**
     * @var string
     */
    private $building;

    /**
     * @var string|null
     */
    private $flat;

    /**
     * @var string
     */
    private $postal_code;

    /**
     * @var string|null
     */
    private $mail;

    /**
     * @var string|null
     */
    private $registration_data_edited;

    /**
     * @var string|null
     */
    private $additional_information;

    public function __construct(LegalForm $legal_form, string $country_code, string $city, string $street, string $building, string $postal_code, string $tax_id_prefix = null, string $tax_id = null, string $company_name = null, string $name = null, string $surname = null, string $flat = null, string $mail = null, string $registration_data_edited = null, string $additional_information = null)
    {
        $this->legal_form = $legal_form;
        $this->country_code = $country_code;
        $this->tax_id_prefix = $tax_id_prefix;
        $this->tax_id = $tax_id;
        $this->company_name = $company_name;
        $this->name = $name;
        $this->surname = $surname;
        $this->city = $city;
        $this->street = $street;
        $this->building = $building;
        $this->flat = $flat;
        $this->postal_code = $postal_code;
        $this->mail = $mail;
        $this->registration_data_edited = $registration_data_edited;
        $this->additional_information = $additional_information;
    }

    public function getLegalForm(): LegalForm
    {
        return $this->legal_form;
    }

    public function getCountryCode(): string
    {
        return $this->country_code;
    }

    public function getTaxIdPrefix(): ?string
    {
        return $this->tax_id_prefix;
    }

    public function getTaxId(): ?string
    {
        return $this->tax_id;
    }

    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getBuilding(): string
    {
        return $this->building;
    }

    public function getFlat(): ?string
    {
        return $this->flat;
    }

    public function getPostalCode(): string
    {
        return $this->postal_code;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function getRegistrationDataEdited(): ?string
    {
        return $this->registration_data_edited;
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->additional_information;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
