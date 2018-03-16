<?php declare(strict_types = 1);

namespace Keboola\OneDriveExtractor\MicrosoftGraphApi;

use GuzzleHttp;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Model;

class Shares
{

    /**
     * @var Api
     */
    private $api;

    /**
     * Files constructor.
     *
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Generate magic "sharing url"
     * https://docs.microsoft.com/en-ca/onedrive/developer/rest-api/api/shares_get#encoding-sharing-urls
     *
     * @param string $url
     * @return string
     */
    public function generateSharingUrl(string $url): string
    {
        $encode = base64_encode($url);
        $sharingUrl = 'u!' . str_replace('+', '-', str_replace('/', '_', rtrim($encode, '=')));

        return $sharingUrl;
    }

    /**
     * @param string $link
     * @return FileMetadata
     * @throws Exception\InvalidSharingUrl
     */
    public function getSharesDriveItemMetadata(string $link)
    {
        $sharingUrl = $this->generateSharingUrl($link);

        try {
            /** @var Model\DriveItem $sharedDriveItem */
            $sharedDriveItem = $this->api->getApi()
                //->createRequest('GET', sprintf('/shares/%s', $encode)) // get sharing object
                //->setReturnType(Model\SharedDriveItem::class)
                ->createRequest('GET', sprintf('/shares/%s/driveItem', $sharingUrl)) // get drive item directly
                ->setReturnType(Model\DriveItem::class)
                ->execute();
        } catch(GraphException | GuzzleHttp\Exception\ClientException $e) {
            throw new Exception\InvalidSharingUrl(
                sprintf('Given url "%s" cannot be loaded ad OneDrive object', $link)
            );
        }

        return FileMetadata::initByOneDriveModel($sharedDriveItem);
    }

}
