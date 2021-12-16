<?php


class UriDB
{
    private string $connString;
    private PDO $pdo;
    public function __construct($host, $port, $dbname, $user, $password)
    {
       $this->connString = sprintf(
           "pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s",
           $host,
           $port,
           $dbname,
           $user,
           $password
       );
       $this->pdo = new PDO($this->connString);

    }

    public function Query(string $statement, int $mode) {
        return $this->pdo->query($statement, $mode);
    }

    public function QueryPromos(string $statement) {
        $q= $this->Query($statement, PDO::FETCH_ASSOC);
        if ($q==false)  return [];

        foreach ($q->fetchAll() as $item) {
            $result[] = $item["code"];
        }

        return $result;
    }

    public function GetAllPromos() {
       return $this->QueryPromos("SELECT * FROM promo");
    }

    public function Add(string $item):int {
        $q = $this->pdo->prepare("INSERT INTO promo(code) VALUES(:code) ON CONFLICT DO NOTHING");
        $q->execute(["code"=>$item]);
        return $this->pdo->lastInsertId();
    }

}