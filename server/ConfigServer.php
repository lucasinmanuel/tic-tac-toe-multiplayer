<?php

namespace Config;

use Exception;
use SplObjectStorage;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Config\ConfigService;

final class ConfigServer implements MessageComponentInterface
{
    private $clients;

    private $handleData;

    private $configService;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();

        $this->handleData = [
            "type" => "",
            "data" => [
                "your_id" => "",
                "opponent_list" => []
            ]
        ];

        $this->configService = new ConfigService();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        try {
            $this->clients->attach($conn);
            array_push($this->handleData["data"]["opponent_list"], $conn->resourceId);
            $handleData = $this->handleData;

            $handleData = $this->configService->removingOwnIdFromTheList($handleData, $conn->resourceId);
            $handleData = $this->configService->resettingPages($handleData, $conn->resourceId, "init");

            $conn->send(json_encode($handleData));
        } catch (Exception $e) {
            printf($e->getMessage());
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $json = json_decode($msg);
        $handleData = $this->handleData;
        $handleData = $this->configService->removingOwnIdFromTheList($handleData, $from->resourceId);
        switch ($json->type) {
            case "refresh":
                $handleData = $this->configService->resettingPages($handleData, $from->resourceId, "refresh");
                $from->send(json_encode($handleData));
                break;
            case "page-navigation":
                $handleData = $this->configService->pageNavigation($handleData, $from->resourceId, $json->direction, $json->page, "page-navigation");
                $from->send(json_encode($handleData));
                break;
            case "page-navigation-search":
                $temporaryList["data"]["opponent_list"] = [];
                //FILTRANDO OS IDS PROCURADOS
                foreach ($handleData["data"]["opponent_list"] as $opponent_id) {
                    if (str_contains($opponent_id, $json->value)) {
                        array_push($temporaryList["data"]["opponent_list"], $opponent_id);
                    }
                }
                $handleData = $this->configService->pageNavigation($temporaryList, $from->resourceId, $json->direction, $json->page, "page-navigation-search");
                $from->send(json_encode($handleData));
                break;
            case "search-value":
                $temporaryList["data"]["opponent_list"] = [];
                //FILTRANDO OS IDS PROCURADOS
                foreach ($handleData["data"]["opponent_list"] as $opponent_id) {
                    if (str_contains($opponent_id, $json->value)) {
                        array_push($temporaryList["data"]["opponent_list"], $opponent_id);
                    }
                }
                $handleData = $this->configService->resettingPages($temporaryList, $from->resourceId, "receive-search");
                $from->send(json_encode($handleData));
                break;
            case "challenge-invite":
                //ADICIONANDO O ID DE QUEM ENVIOU O CONVITE
                $json->sender_id = $from->resourceId;
                $json->type = "challenge-receive";
                //ENVIANDO O CONVITE PARA DESTINATÁRIO
                foreach ($this->clients as $client) {
                    if ($client->resourceId === $json->addressee_id) {
                        $client->send(json_encode($json));
                    }
                }
                break;
            case "challenge-accepted":
                $match = $this->configService->challengeAccepted($json);
                //ENVIANDO INFORMAÇÕES DA PARTIDA PARA AMBOS
                foreach ($this->clients as $client) {
                    if (
                        $client->resourceId === $json->sender_id
                        || $client->resourceId === $json->addressee_id
                    ) {
                        $client->send(json_encode($match));
                    }
                }
                break;
            case "challenge-update":
                $match = $this->configService->challengeUpdate($json, $from->resourceId);
                //ENVIANDO INFORMAÇÕES DA PARTIDA PARA AMBOS
                foreach ($this->clients as $client) {
                    if (
                        $client->resourceId == $json->you->id
                        || $client->resourceId == $json->opponent->id
                    ) {
                        $client->send(json_encode($match));
                    }
                }
                break;
            case "rematch-invite":
                $rematch = $this->configService->rematchInvite($json);
                //VERIFICANDO O ID DO OPONENTE
                $opponent_id = $from->resourceId != $rematch["sender_id"] ?
                    $rematch["sender_id"] : $rematch["addressee_id"];
                //ENVIANDO O CONVITE O OPONENTE
                foreach ($this->clients as $client) {
                    if ($client->resourceId == $opponent_id) {
                        $client->send(json_encode($rematch));
                    }
                }
                break;
            case "rematch-accepted":
                $rematch = $this->configService->rematchAccepted($json);
                //ENVIANDO INFORMAÇÕES DA PARTIDA PARA AMBOS
                foreach ($this->clients as $client) {
                    if (
                        $client->resourceId === $json->sender_id
                        || $client->resourceId === $json->addressee_id
                    ) {
                        $client->send(json_encode($rematch));
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);

        //DELETANDO VOCÊ DA LISTA DE OPONENTES
        $index = array_search($conn->resourceId, $this->handleData["data"]["opponent_list"]);
        array_splice($this->handleData["data"]["opponent_list"], $index, 1);

        //DELETANDO PARTIDA CASO ESTEJA NELA E PEGANDO O ID DO OPONENTE
        $opponent_id = $this->configService->DeletingMatchIfYouAreInA($conn->resourceId);

        if ($opponent_id != -1) {
            $warning = [];
            $warning["type"] = "opponent-disconnected";

            //ENVIANDO O ALERTA O OPONENTE
            foreach ($this->clients as $client) {
                if ($client->resourceId == $opponent_id) {
                    $client->send(json_encode($warning));
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, Exception $exception): void
    {
        $conn->close();
    }
}
