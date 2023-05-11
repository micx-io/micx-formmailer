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
use Lack\Subscription\Type\T_Subscription;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Micx\FormMailer\Config\Config;
use Micx\FormMailer\Config\T_Formmailer;
use Phore\Mail\PhoreMailer;
use Psr\Http\Message\ServerRequestInterface;

AppLoader::extend(function (BraceApp $app) {


    $app->router->on("GET@/v1/formmailer/formmail.js", function (BraceApp $app, ServerRequestInterface $request) {
        $data = file_get_contents(__DIR__ . "/../src/formmail.js");

        $subscriptionId = $request->getQueryParams()["subscription_id"] ?? throw new \InvalidArgumentException("Missing parameter subscription_id");
        $data = str_replace(
            ["%%ENDPOINT_URL%%", "%%ERROR%%"],
            [
                "//" . $app->request->getUri()->getHost() . "/v1/formmailer/send?subscription_id={$subscriptionId}",
                ""
            ],
            $data
        );

        return $app->responseFactory->createResponseWithBody($data, 200, ["Content-Type" => "application/javascript"]);
    });

    $app->router->on("POST@/v1/formmailer/send", function(array $body, T_Subscription $subscription,  ServerRequest $request) {
        $mailer = new PhoreMailer();

        $preset = $request->getQueryParams()["preset"] ?? "default";

        $config = $subscription->getClientPrivateConfig(null, T_Formmailer::class);

        $template = $config->templates[$preset] ?? throw new \InvalidArgumentException("No template defined for preset '$preset'");

        $tpl = file_get_contents(__DIR__ . "/../src/defaultMail.txt");
        if ($template->template_url !== null && $template->template_url !== "")
            $tpl = phore_http_request($template->template_url)->send(true)->getBody();

        $bodyDataStr = "";
        $dataArray = [];

        foreach ($body as $key => $value) {

            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $bodyDataStr .= "\n\n$key.$key2: $value2";
                    $dataArray[] = ["key" => $key . "." .  $key2, "value" => $value];
                }

            } else {
                $bodyDataStr .= "\n\n$key: $value";
                $dataArray[] = ["key" => $key, "value" => $value];
            }
        }
        $body["__DATA__"] = $bodyDataStr;
        $body["dataArray"] = $dataArray;

        $mailer->setSmtpDirectConnect(CONF_SMTP_SENDER_HOST);


        if ($template->mail_to !== null) {
            $mailer->phpmailer->addAddress($template->mail_to);
        }
        $mailer->prepare($tpl, $body);

        $mailer->curMail->send();
        return ["ok"];
    });


    $app->router->on("GET@/v1/formmailer", function() {
        return ["system" => "micx formmailer", "status" => "ok"];
    });

});
