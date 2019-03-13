<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Trait ModuleCustomTrait - default implementation of ModuleCustomInterface
 */
trait ModuleCustomTrait
{
    /**
     * The person or organisation who created this module.
     *
     * @return string
     */
    public function customModuleAuthorName(): string
    {
        return '';
    }

    /**
     * The version of this module.
     *
     * @return string  e.g. '1.2.3'
     */
    public function customModuleVersion(): string
    {
        return '';
    }

    /**
     * A URL that will provide the latest version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        return '';
    }

    /**
     * Where to get support for this module.  Perhaps a github respository?
     *
     * @return string
     */
    public function customModuleSupportUrl(): string
    {
        return '';
    }

    /**
     * Additional/updated translations.
     *
     * @param string $language
     *
     * @return string[]
     */
    public function customTranslations(string $language): array
    {
        return [];
    }


    /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourceFolder(): string
    {
        return WT_ROOT . 'resources/';
    }

    /**
     * Create a URL for an asset.
     *
     * @param string $asset e.g. "css/theme.css" or "img/banner.png"
     *
     * @return string
     */
    public function assetUrl(string $asset): string
    {
        $file = $this->resourceFolder() . $asset;

        // Add the file's modification time to the URL, so we can set long expiry cache headers.
        $hash = filemtime($file);

        return route('module', [
            'module' => $this->name(),
            'action' => 'asset',
            'asset'  => $asset,
            'hash'   => $hash,
        ]);
    }

    /**
     * Serve a CSS/JS file.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getAssetAction(Request $request): Response
    {
        // The file being requested.  e.g. "css/theme.css"
        $asset = $request->get('asset');

        // Do not allow requests that try to access parent folders.
        if (Str::contains($asset, '..')) {
            throw new AccessDeniedHttpException($asset);
        }

        // Find the file for this asset.
        // Note that we could also generate CSS files using views/templates.
        // e.g. $file = view(....
        $file = $this->resourceFolder() . $asset;

        if (!file_exists($file)) {
            throw new NotFoundHttpException($file);
        }

        $content     = file_get_contents($file);
        $expiry_date = Carbon::now()->addYears(10);

        $extension = pathinfo($asset, PATHINFO_EXTENSION);

        $mime_types = [
            'css'  => 'text/css',
            'gif'  => 'image/gif',
            'js'   => 'application/javascript',
            'jpg'  => 'image/jpg',
            'jpeg' => 'image/jpg',
            'json' => 'application/json',
            'png'  => 'image/png',
            'txt'  => 'text/plain',
        ];

        $mime_type = $mime_types[$extension] ?? 'application/octet-stream';

        $headers = [
            'Content-Type' => $mime_type,
        ];

        $response = new Response($content, Response::HTTP_OK, $headers);

        return $response
            ->setExpires($expiry_date);
    }
}
