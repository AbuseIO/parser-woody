<?php

namespace AbuseIO\Parsers;

class Woody extends Parser
{
    /**
     * Create a new Woody instance
     */
    public function __construct($parsedMail, $arfMail)
    {
        parent::__construct($parsedMail, $arfMail, $this);
    }

    /**
     * Parse attachments
     * @return Array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        $this->feedName = 'default';

        if ($this->isKnownFeed() && $this->isEnabledFeed() && $this->hasArfMail()) {
            if (preg_match_all('/([\w\-]+): (.*)[ ]*\r?\n/', $this->arfMail['report'], $regs)) {
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
            } else {
                $this->warningCount++;
            }
        }

        return $this->success();
    }
}

