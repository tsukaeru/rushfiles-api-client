<?php

namespace RushFiles;

use Illuminate\Support\Collection;

class PublicLink
{
    public const STRING = 'string';
    public const OBJECT = 'object';

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @param array $rawData Properties returned by RushFiles API
     * @param string $domain Domain from where public link was retrieved
     */
    public function __construct($rawData, $domain)
    {
        $this->properties = Collection::make($rawData);
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->properties->get('Id');
    }

    /**
     * @return string
     */
    public function getFullLink()
    {
        return "https://{$this->domain}/client/publiclink.aspx?id={$this->getId()}";
    }

    /**
     * @return string
     */
    public function getShareId()
    {
        return $this->properties->get('ShareId');
    }

    /**
     * @return string
     */
    public function getVirtualFileId()
    {
        return $this->properties->get('VirtualFileId');
    }

    /**
     * @return bool
     */
    public function isFile()
    {
        return $this->properties->get('IsFile');
    }

    /**
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->properties->get('CreatedBy');
    }

    /**
     * The method is somewhat unreliable as it shows whether or not
     * the IsPasswordEnabled was set to true when creating the link.
     * Whether the password was really set is another matter.
     *
     * e.g. if when creating link the following properties were
     * sent to API:
     * [
     *     ...
     *     'Password' => null,
     *     'EnablePassword' => true
     * ]
     * this method will return TRUE but link won't be protected with
     * any password.
     *
     * This package makes sure that Password and EnablePassword are
     * in sync, but we cannot guarantee the truthfulness of
     * IsPasswordProtected for link created with different clients.
     *
     * @return bool
     */
    public function isPasswordEnabled()
    {
        return $this->properties->get('IsPasswordEnabled');
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->properties->get('Message');
    }
}