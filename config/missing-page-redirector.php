<?php

declare(strict_types=1);

use App\Redirects\DatabaseRedirector;
use Symfony\Component\HttpFoundation\Response;

return [
    /*
     * This is the class responsible for providing the URLs which must be redirected.
     * The only requirement for the redirector is that it needs to implement the
     * `Spatie\MissingPageRedirector\Redirector\Redirector`-interface
     *
     * We override the default ConfigurationRedirector with a DB-backed one
     * so admins can edit the 301-map via Filament without a deploy.
     */
    'redirector' => DatabaseRedirector::class,

    /*
     * By default the package will only redirect 404s. If you want to redirect on other
     * response codes, just add them to the array. Leave the array empty to redirect
     * always no matter what the response code.
     */
    'redirect_status_codes' => [
        Response::HTTP_NOT_FOUND,
    ],

    /*
     * When using the `ConfigurationRedirector` you can specify the redirects in this array.
     * You can use Laravel's route parameters here.
     */
    'redirects' => [
        //        '/non-existing-page' => '/existing-page',
        //        '/old-blog/{url}' => '/new-blog/{url}',
    ],

];
