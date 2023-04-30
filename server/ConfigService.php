<?php

namespace Config;

use Config\ChallengeList;

final class ConfigService
{
    private $limit;
    private $challengeList;
    public function __construct()
    {
        $this->limit = 12;
        $this->challengeList = new ChallengeList();
    }
    public function resettingPages($handleData, $your_id, $type): array
    {
        $sizeList = sizeof($handleData["data"]["opponent_list"]);
        $number_pages = ceil($sizeList / $this->limit);
        $filtered = array_slice($handleData["data"]["opponent_list"], 0, 12);
        $handleData["type"] = $type;
        $handleData["data"]["your_id"] = $your_id;
        $handleData["data"]["page"] = 1;
        $handleData["data"]["number_pages"] = $number_pages;
        $handleData["data"]["opponent_list"] = $filtered;
        return $handleData;
    }
    public function removingOwnIdFromTheList($handleData, $your_id): array
    {
        $index = array_search($your_id, $handleData["data"]["opponent_list"]);
        array_splice($handleData["data"]["opponent_list"], $index, 1);
        return $handleData;
    }
    public function pageNavigation($handleData, $your_id, $direction, $page, $type): array
    {
        $sizeList = sizeof($handleData["data"]["opponent_list"]);
        $number_pages = ceil($sizeList / $this->limit);

        if ($direction == "previous") {
            if ($page > 1) {
                $page -= 1;
            }
        } else if ($direction == "next") {
            if ($page < $number_pages) {
                $page += 1;
            }
        }
        $end = $page * $this->limit;
        $start = $end - $this->limit;
        $filtered = array_slice($handleData["data"]["opponent_list"], $start, $end);

        $handleData["type"] = $type;
        $handleData["data"]["your_id"] = $your_id;
        $handleData["data"]["page"] = $page;
        $handleData["data"]["number_pages"] = $number_pages;
        $handleData["data"]["opponent_list"] = $filtered;

        return $handleData;
    }
    public function challengeAccepted($json): array
    {
        //ADICIONANDO A PARTIDA A LISTA
        $match = $this->challengeList->add($json->sender_id, $json->addressee_id);

        $match["type"] = "challenge-started";

        $match["turn"] = $this->challengeList->randomTurnChoice($json->sender_id, $json->addressee_id);

        return $match;
    }
    public function challengeUpdate($json, $your_id): array
    {
        //DESCOBRINDO O ID DA PARTIDA: ID DO REMETENTE + DESTINATÁRIO
        $match_id = $this->challengeList->findOutMatchId($json);

        $your_choices = $json->you->choices;
        $opponent_choices = $json->opponent->choices;

        $result = $this->challengeList->victoryVerification($your_choices, $opponent_choices);
        $your_key = $json->you->nickname;

        if ($result == "continue") {
            //A PARTIDA CONTINUA
            $match = $this->challengeList->update($match_id, $your_key, $your_choices);
            $match["turn"] = $json->opponent->id;
        } else {
            //A PARTIDA TERMINOU, POIS ALGUÉM GANHOU
            $match = $this->challengeList->endMatch($match_id, $your_key, $your_choices, $result);
            //CONFERE SE FOI EMPATE
            $match["turn"] = $result == "ties" ? $result : "$result-$your_id";
        }

        $match["type"] = "challenge-update";

        return $match;
    }
    public function rematchInvite($json): array
    {
        $rematch = [];
        $rematch["sender_id"] = $this->challengeList->getSenderId($json);
        $rematch["addressee_id"] = $this->challengeList->getAddresseeId($json);
        $rematch["type"] = "rematch-receive";
        return $rematch;
    }
    public function rematchAccepted($json)
    {
        $match_id = $json->sender_id . $json->addressee_id;

        //RESETANDO AS ESCOLHAS DOS JOGADORES
        $this->challengeList->update($match_id, "sender", []);
        $rematch = $this->challengeList->update($match_id, "addressee", []);

        $rematch["turn"] = $this->challengeList->randomTurnChoice($json->sender_id, $json->addressee_id);
        $rematch["type"] = "rematch-started";

        return $rematch;
    }
    public function DeletingMatchIfYouAreInA($your_id)
    {
        return $this->challengeList->deleteChallenge($your_id);
    }
}
