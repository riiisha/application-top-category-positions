<?php

namespace App\Command;

use App\Entity\Positions;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:get-positions',
    description: 'Получение позиции приложения в топе за последние 30 дней',
)]
class GetPositionsCommand extends Command
{
    private string $api = 'https://api.apptica.com/package/top_history/%d/%d?date_from=%s&date_to=%s&%s';

    private EntityManagerInterface $em;

    private SerializerInterface $serializer;

    /**
     * UploadOrderCommand constructor.
     */
    public function __construct(
        EntityManagerInterface $em,
        SerializerInterface    $serializer,
    )
    {
        $this->em = $em;
        $this->serializer = $serializer;

        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $applicationId = 1421444;
        $countryId = 1;
        $dateFrom = ((new DateTime())->sub(new DateInterval('P30D')))->format('Y-m-d');
        $dateTo = (new DateTime())->format('Y-m-d');
        $apiKey = 'B4NKGg=fVN5Q9KVOlOHDx9mOsKPAQsFBlEhBOwguLkNEDTZvKzJzT3l';
        $getPositionsUrl = sprintf($this->api, $applicationId, $countryId, $dateFrom, $dateTo, $apiKey);

        $client = HttpClient::create();
        try {
            $response = $client->request('GET', $getPositionsUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if (200 == $response->getStatusCode()) {
                $positions = json_decode($response->getContent(), true) ?? [];
                $newCategory = [];

                foreach ($positions['data'] as $keyCategory => $category) {
                    foreach ($category as $subCategory) {
                        foreach ($subCategory as $keyDate => $position) {
                            if (!is_null($position)) {
                                $newCategory[$keyCategory][$keyDate][] = $position;
                            }
                        }
                    }
                }

                foreach ($newCategory as $key => $positions) {
                    foreach ($positions as $date => $item) {
                        $positionRes = [
                            'category' => $key,
                            'date' => (new DateTime($date))->format('Y-m-d\TH:i:sP'),
                            'position' => min($item)
                        ];
                        $data = $this->serializer->deserialize(json_encode($positionRes), Positions::class, 'json');
                        $this->em->merge($data);
                    }
                }
                try {
                    $this->em->flush();
                    $output->writeln("Запись прошла успешно");
                } catch (\Exception $exception) {
                    $output->writeln("Ошибка записи" . $exception);
                }
            } else {
                $output->writeln("Ошибка запроса. Status code: " . $response->getStatusCode());
            }
        } catch (TransportExceptionInterface $e) {
            $output->writeln("Ошибка запроса: " . $e);
        }

        return Command::SUCCESS;
    }
}
