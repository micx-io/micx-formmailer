<?php
namespace App;



use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use http\Message\Body;
use Lack\OidServer\Base\Ctrl\AuthorizeCtrl;
use Lack\OidServer\Base\Ctrl\SignInCtrl;
use Lack\OidServer\Base\Ctrl\LogoutCtrl;
use Lack\OidServer\Base\Ctrl\TokenCtrl;
use Lack\OidServer\Base\Tpl\HtmlTemplate;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Micx\FormMailer\Config\Config;
use Phore\Mail\PhoreMailer;
use Psr\Http\Message\ServerRequestInterface;

AppLoader::extend(function (BraceApp $app) {


    $app->router->on("GET@/v1/formmailer/formmail.js", function (BraceApp $app, string $subscriptionId, Config $config, ServerRequestInterface $request) {
        $data = file_get_contents(__DIR__ . "/../src/formmail.js");

        $error = "";
        $origin = $request->getHeader("referer")[0] ?? null;
        if ($origin !== null && ! origin_match($origin, $config->allow_origins)) {
            $error = "Invalid origin: '$origin' - not allowed for subscription_id '$subscriptionId'";
        }

        $data = str_replace(
            ["%%ENDPOINT_URL%%", "%%ERROR%%"],
            [
                "//" . $app->request->getUri()->getHost() . "/v1/formmailer/send?subscription_id=$subscriptionId",
                $error
            ],
            $data
        );

        return $app->responseFactory->createResponseWithBody($data, 200, ["Content-Type" => "application/javascript"]);
    });

    $app->router->on("POST@/v1/formmailer/send", function(array $body, Config $config) {
        $mailer = new PhoreMailer();

        $tpl = phore_http_request($config->template_url)->send(true)->getBody();

        $body["__DATA__"] = "";
        foreach ($body as $key => $value) {
            $body["__DATA__"] .= "\n\n$key: $value";
        }

        $mailer->setSmtpDirectConnect("micx.host");
        $mailer->send($tpl, $body);

        return ["ok"];
    });


    $app->router->on("GET@/v1/formmailer", function() {
        return ["system" => "micx formmailer", "status" => "ok"];
    });

});
