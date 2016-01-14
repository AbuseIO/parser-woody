<?php

namespace AbuseIO\Parsers;

use AbuseIO\Models\Incident;

/**
 * Class Woody
 * @package AbuseIO\Parsers
 */
class Woody extends Parser
{
    /**
     * Create a new Woody instance
     *
     * @param \PhpMimeMailParser\Parser $parsedMail phpMimeParser object
     * @param array $arfMail array with ARF detected results
     */
    public function __construct($parsedMail, $arfMail)
    {
        parent::__construct($parsedMail, $arfMail, $this);
    }

    /**
     * Parse attachments
     * @return array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        $this->feedName = 'default';

        if ($this->isKnownFeed() && $this->isEnabledFeed() && $this->hasArfMail()) {
            if (preg_match_all('/([\w\-]+): (.*)[ ]*\r?\n/', $this->arfMail['report'], $regs)) {
                $report = array_combine($regs[1], $regs[2]);

                if ($this->hasRequiredFields($report) === true) {
                    // incident has all requirements met, filter and add!
                    if ($report['Feedback-Type'] != 'abuse') {
                        return $this->failed(
                            "Unabled to detect the report type from this notifier"
                        );
                    }

                    $report = $this->applyFilters($report);

                    $report['evidence'] = $this->arfMail['evidence'];

                    $incident = new Incident();
                    $incident->source      = config("{$this->configBase}.parser.name");
                    $incident->source_id   = false;
                    $incident->ip          = $report['Source-IP'];
                    $incident->domain      = false;
                    $incident->uri         = false;
                    $incident->class       = config("{$this->configBase}.feeds.{$this->feedName}.class");
                    $incident->type        = config("{$this->configBase}.feeds.{$this->feedName}.type");
                    $incident->timestamp   = strtotime($report['Received-Date']);
                    $incident->information = json_encode($report);

                    $this->incidents[] = $incident;

                }
            } else {
                $this->warningCount++;
            }
        }

        return $this->success();
    }
}
