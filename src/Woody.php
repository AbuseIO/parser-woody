<?php

namespace AbuseIO\Parsers;

use Ddeboer\DataImport\Reader;
use Ddeboer\DataImport\Writer;
use Ddeboer\DataImport\Filter;
use Log;
use ReflectionClass;

class Woody extends Parser
{
    /**
     * Create a new Blocklistde instance
     */
    public function __construct($parsedMail, $arfMail)
    {
        parent::__construct($parsedMail, $arfMail);
    }

    /**
     * Parse attachments
     * @return Array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        // Generalize the local config based on the parser class name.
        $reflect = new ReflectionClass($this);
        $this->configBase = 'parsers.' . $reflect->getShortName();

        Log::info(
            get_class($this) . ': Received message from: ' .
            $this->parsedMail->getHeader('from') . " with subject: '" .
            $this->parsedMail->getHeader('subject') . "' arrived at parser: " .
            config("{$this->configBase}.parser.name")
        );

        $this->feedName = 'default';

        if ($this->isKnownFeed() && $this->isEnabledFeed() && $this->hasArfMail()) {
            preg_match_all('/([\w\-]+): (.*)[ ]*\r?\n/', $this->arfMail['report'], $regs);
            $report = array_combine($regs[1], $regs[2]);

            if ($this->hasRequiredFields($report) === true) {
                // Event has all requirements met, filter and add!
                if ($report['Feedback-Type'] != 'abuse') {
                    return $this->failed(
                        "Unabled to detect the report type from this notifier"
                    );
                }

                $report = $this->applyFilters($report);

                $report['evidence'] = $this->arfMail['evidence'];

                $this->events[] = [
                    'source'        => config("{$this->configBase}.parser.name"),
                    'ip'            => $report['Source-IP'],
                    'domain'        => false,
                    'uri'           => false,
                    'class'         => config("{$this->configBase}.feeds.{$this->feedName}.class"),
                    'type'          => config("{$this->configBase}.feeds.{$this->feedName}.type"),
                    'timestamp'     => strtotime($report['Received-Date']),
                    'information'   => json_encode($report),
                ];
            }
        }

        return $this->success();
    }
}
