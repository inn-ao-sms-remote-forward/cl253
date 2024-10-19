<?php

declare(strict_types=1);

namespace InnStudio\Cl253;

class App
{
    private $apiAccountId = '';

    private $apiAccountPwd = '';

    private $msg = '';

    private $apiUrl = '';

    private $phoneNumber = 0;

    private $verificationCode = 0;

    private $minutes = 0;

    private $configPath = '';

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->setPhoneNumber();
        $this->setCode();
        $this->setMsg();
        $this->setConfig();

        if ($this->send()) {
            exit(json_encode([
                'code' => 0,
            ]));
        }

        exit(json_encode([
            'code' => -1,
        ]));
    }

    private function setCode(): void
    {
        $this->verificationCode = (int) filter_input(\INPUT_GET, 'code', \FILTER_VALIDATE_INT);

        if (!$this->verificationCode) {
            exit('Invalid verification code');
        }
    }

    private function setMsg(): void
    {
        $this->msg = (string) filter_input(\INPUT_GET, 'sms', \FILTER_DEFAULT);

        if (!$this->msg) {
            exit('Invalid message.');
        }
    }

    private function setPhoneNumber(): void
    {
        $this->phoneNumber = (int) filter_input(\INPUT_GET, 'number', \FILTER_VALIDATE_INT);

        if (!$this->phoneNumber) {
            exit('Invalid phone number');
        }
    }

    private function setConfig(): void
    {
        if (!is_readable($this->configPath)) {
            exit('Invalid config file path.');
        }

        $config = json_decode((string) file_get_contents($this->configPath), true);

        if (!\is_array($config)) {
            exit('Invalid config file content.');
        }

        [
            'apiAccountId' => $this->apiAccountId,
            'apiAccountPwd' => $this->apiAccountPwd,
            'apiUrl' => $this->apiUrl,
        ] = $config;
    }

    private function send(): bool
    {
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
        ]);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, \CURLOPT_POST, 1);
        curl_setopt($ch, \CURLOPT_POSTFIELDS, json_encode([
            'account' => $this->apiAccountId,
            'password' => $this->apiAccountPwd,
            'phone' => $this->phoneNumber,
            'msg' => urlencode($this->msg),
            'report' => 'true',
        ]));
        curl_setopt($ch, \CURLOPT_TIMEOUT, 60);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return false;
        }

        $json = json_decode($res, true);

        if (!$json) {
            return false;
        }

        if ('0' !== $json['code']) {
            error_log($json['errorMsg']);

            return false;
        }

        return true;
    }
}
