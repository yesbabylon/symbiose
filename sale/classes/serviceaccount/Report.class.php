<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\serviceaccount;

use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

class Report extends \equal\orm\Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short readable identifier of the report.',
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date of the most recent line on the report (equivalent to date_to).',
                'help'              => 'Should be the last day included in the report date-range @ 23:59:59.',
                'default'           => time(),
                'dependents'        => ['name']
            ],

            'date_from' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Day after the previous published report last date.',
                'help'              => 'There might be some remaining time entries included in report whose date precedes the report\'s date_from.',
                'function'          => 'calcDateFrom',
                'store'             => true,
                'dependents'        => ['is_sendable']
            ],

            'service_account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\serviceaccount\ServiceAccount',
                'description'       => 'The service account the line belongs to.',
                'dependents'        => ['customer_id', 'balance_old', 'has_lines', 'is_sendable']
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the report relates to (from service account).',
                'relation'          => ['service_account_id' => ['customer_id']],
                'store'             => true,
                'instant'           => true
            ],

            'has_lines' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcHasLines',
                'description'       => 'Flag for telling if the report has at least one line.'
            ],

            'service_account_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\serviceaccount\ServiceAccountEntry',
                'foreign_field'     => 'report_id',
                'order'             => 'date',
                'description'       => 'SA Lines assigned to the report.',
                'dependents'        => ['has_lines']
            ],

            'link' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'description'       => 'URL for generating the PDF version of the report.',
                'function'          => 'calcLink',
                'readonly'          => true
            ],

            'pdf_data' => [
                'type'              => 'computed',
                'result_type'       => 'binary',
                'usage'             => 'application/pdf',
                'description'       => 'Generated PDF data for the report.',
                'function'          => 'calcPdfData',
                'store'             => true
            ],

            'is_sendable' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Flag telling if the Report is ready to be sent according to the app logic.',
                'help'              => 'The Report must be released, use the `send` reporting mode, and not start before `reporting_from`.',
                'function'          => 'calcIsSendable',
                'store'             => true
            ],

            'total_points' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'function'          => 'calcTotalPoints',
                'description'       => 'Sum of all TT lines (negative value).'
            ],

            'total_credits' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'function'          => 'calcTotalCredits',
                'description'       => 'Sum of all CC lines.'
            ],

            'balance_old' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcBalanceOld',
                'description'       => 'Previous balance of the related Service Account (from last previous report).',
                'help'              => "Tells the balance after invoicing the lines assigned to the report.
                                        For released reports, this value might differ from the relating ServiceAccount's balance."
            ],

            'balance_new' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcBalanceNew',
                'store'             => true,
                'description'       => 'New balance of the related Service Account.',
                'help'              => "Tells the balance after invoicing the lines assigned to the report.
                                        This value is automatically updated upon status change (when report is released).
                                        For pending reports, this value might differ from the relating ServiceAccount's balance."
            ],

            'is_empty' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'store'             => true,
                'function'          => 'calcIsEmpty',
                'description'       => 'Report does not contain any line.'
            ],

            'mails_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'core\Mail',
                'foreign_field'     => 'object_id',
                'domain'            => ['object_class', '=', self::getType()]
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',                    // the report is a draft
                    'released',                   // final report: cannot be updated anymore
                    'archived',
                    'sent'
                ],
                'description'       => 'Status of the report.',
                'default'           => 'pending',
                'onupdate'          => 'onupdateStatus',
                'dependents'        => ['name', 'is_sendable']
            ]
        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['date', 'status']);
        foreach($self as $id => $report) {
            $result[$id] = sprintf(
                '%d-%04d %s',
                date('Ymd', $report['date']),
                $id,
                $report['status'] === 'pending' ? ' (DRAFT)' : ''
            );
        }

        return $result;
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'release' => [
                'description' => 'Release the report and update its service account.',
                'help'        => 'Only pending reports can be released.',
                'policies'    => [],
                'function'    => 'doRelease'
            ]
        ]);
    }

    protected static function doRelease($self) {
        $self->read([
            'status',
            'is_empty',
            'service_account_id' => ['m_reporting']
        ]);

        foreach($self as $report) {
            if($report['status'] !== 'pending') {
                throw new \Exception('already_released_report', EQ_ERROR_NOT_ALLOWED);
            }
        }

        foreach($self as $id => $report) {
            $status = (
                    ($report['service_account_id']['m_reporting'] ?? null) === 'archive'
                    || $report['is_empty']
                )
                ? 'archived'
                : 'released';

            self::id($id)->update(['status' => $status]);
        }
    }

    /**
     * Hook invoked upon status change.
     * When a report is released, all SA lines that are attached to it are locked; and the current balance of the related service account is updated.
     * #memo - a reports is generated only once: upon update, a new draft report is created.
     *
     * @param  \equal\orm\Collection    $self       Collection instance.
     * @param  array                    $values     Associative array holding the new values that have been assigned.
     * @return void
     */
    public static function onupdateStatus($self, $values) {
        if(isset($values['status']) && in_array($values['status'], ['released', 'archived', 'sent'])) {
            $self->read(['service_account_id', 'service_account_entries_ids']);
            foreach($self as $report) {
                try {
                    $entries_ids = ($report['service_account_entries_ids'] instanceof \equal\orm\Collection)
                        ? $report['service_account_entries_ids']->ids()
                        : (array) $report['service_account_entries_ids'];

                    // update all lines attached to the report
                    if(count($entries_ids)) {
                        ServiceAccountEntry::ids($entries_ids)->update(['locked_date' => time()]);
                    }
                    // update parent service account balance
                    if($report['service_account_id']) {
                        ServiceAccount::id($report['service_account_id'])->update(['balance_current' => null]);
                    }
                }
                catch(\Exception $e) {
                    trigger_error("ORM::error in onupdateStatus - ".$e->getMessage(), EQ_REPORT_ERROR);
                }
            }
        }
    }


    /**
     * Compute the value of the date_from` field: the day following the date_to of the previous report, or 01/01/2023 if none exists.
     * @var \equal\orm\Collection $self
     */
    public static function calcDateFrom($self) {
        $result = [];
        $self->read([
            'date',
            'service_account_id' => ['id', 'reporting_from', 'date_from'],
            'service_account_entries_ids' => ['date']
        ]);

        foreach($self as $id => $report) {
            $previous = self::search([
                    ['service_account_id', '=', $report['service_account_id']['id']],
                    ['status', '<>', 'pending'],
                    ['date', '<', $report['date']]
                ], [
                    'sort'  => ['date' => 'desc'],
                    'limit' => 1
                ])
                ->read(['date'])
                ->first();
            $fallback_date = $report['service_account_id']['reporting_from']
                ?? $report['service_account_id']['date_from']
                ?? null;

            if(!$fallback_date) {
                foreach($report['service_account_entries_ids'] as $entry) {
                    if($entry['date'] && (!$fallback_date || $entry['date'] < $fallback_date)) {
                        $fallback_date = $entry['date'];
                    }
                }
            }

            if(!$fallback_date) {
                $fallback_date = $report['date'];
            }

            $result[$id] = ($previous)?(strtotime('+1 day', $previous['date'])):$fallback_date;
        }
        return $result;
    }

    public static function calcHasLines($self) {
        $result = [];
        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            $result[$id] = (bool) count($report['service_account_entries_ids']);
        }
        return $result;
        // return array_map(fn ($a) => (bool) count((array) $a['service_account_entries_ids']), $self->read(['service_account_entries_ids'])->get());
    }

    public static function calcTotalPoints($self) {
        $result = [];
        $self->read(['service_account_entries_ids' => ['origin_object_class', 'points']]);
        foreach($self as $id => $report) {
            $total_points = 0.0;
            foreach($report['service_account_entries_ids'] as $line) {
                // Time entries consume service account points, which are stored as positive values on entries.
                if($line['origin_object_class'] === 'timetrack\TimeEntry') {
                    $total_points -= $line['points'];
                }
            }
            $result[$id] = $total_points;
        }
        return $result;
    }


    public static function calcTotalCredits($self) {
        $result = [];
        $self->read(['service_account_entries_ids' => ['origin_object_class', 'points']]);
        foreach($self as $id => $report) {
            $total_credits = 0.0;
            foreach($report['service_account_entries_ids'] as $line) {
                if($line['origin_object_class'] !== 'timetrack\TimeEntry') {
                    // Credits and corrections increment the service account balance.
                    $total_credits += $line['points'];
                }
            }
            $result[$id] = $total_credits;
        }
        return $result;
    }

    /**
     * Compute the Service Account Balance before invoicing the lines assigned to the Report.
     * The value of this field should be computed only once and fetched upon creation.
     * #memo - Calculation is triggered upon assignation of the service_account_id.
     */
    public static function calcBalanceOld($self) {
        $result = [];
        $self->read(['date', 'service_account_id']);
        foreach($self as $id => $report) {
            $previous = self::search([
                    ['service_account_id', '=', $report['service_account_id']],
                    ['status', '<>', 'pending'],
                    ['date', '<', $report['date']]
                ], [
                    'sort'  => ['date' => 'desc'],
                    'limit' => 1
                ])
                ->read(['balance_new'])
                ->first();
            // balance_old is the balance_new of the most recent previous report, if any
            $result[$id] = ($previous)?$previous['balance_new']:0.0;
        }
        return $result;
    }

    /**
     * Compute the Service Account Balance before invoicing the lines assigned to the Report.
     * The value of this field should be computed only once and fetched upon creation.
     * #memo - Calculation is triggered upon assignation of the service_account_id.
     */
    public static function calcBalanceNew($self) {
        $result = [];
        $self->read(['balance_old', 'total_points', 'total_credits']);
        foreach($self as $id => $report) {
            $result[$id] = round(
                (float) $report['balance_old'] + (float) $report['total_points'] + (float) $report['total_credits'],
                2
            );
        }
        return $result;
    }

    /**
     * Provide the link for generating the PDF version of the Report.
     */
    public static function calcLink($self) {
        $result = [];
        foreach($self as $id => $report) {
            $result[$id] = '/?get=sale_serviceaccount_Report_render-pdf&id='.$id;
        }
        return $result;
    }

    public static function calcIsEmpty($self) {
        $result = [];
        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            $result[$id] = count($report['service_account_entries_ids']) === 0;
        }
        return $result;
    }

    public static function calcPdfData($self) {
        $result = [];

        foreach($self as $id => $report) {
            $result[$id] = self::generatePdf($id);
        }

        return $result;
    }

    public static function calcIsSendable($self) {
        $result = [];
        $self->read([
            'status',
            'date_from',
            'service_account_id' => ['m_reporting', 'reporting_from']
        ]);
        foreach($self as $id => $report) {
            $service_account = $report['service_account_id'] ?? [];
            $reporting_from = $service_account['reporting_from'] ?? null;

            $result[$id] = $report['status'] === 'released'
                && ($service_account['m_reporting'] ?? null) === 'send'
                && (!$reporting_from || $report['date_from'] >= $reporting_from);
        }
        return $result;
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @var \equal\orm\Collection $self
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($self, $values) {
        $self->read(['status']);
        // #memo - pdf data is generated asynchronously
        // is_sendable can be invalidated from the service account
        $allowed = ['status', 'pdf_data', 'is_sendable'];
        foreach($self as $report) {
            if($report['status'] != 'pending' && count(array_diff(array_keys($values), $allowed)) > 0 ) {
                return ['status' => ['not_allowed' => 'Released Reports cannot be changed.']];
            }
        }
        return parent::canupdate($self);
    }

    public static function candelete($self) {
        $self->read(['status']);
        foreach($self as $report) {
            if($report['status'] != 'pending') {
                return ['status' => ['not_allowed' => 'Only draft Reports can be deleted.']];
            }
        }
        return parent::candelete($self);
    }

    /**
     * Generate a PDF version of a Report, intended for printing.
     *
     * @param int   $id     Identifier of the report to print.
     * @param array $params Accepted params are: show_details, show_logs and view_id.
     */
    public static function generatePdf($id, $params=[]) {
        return \eQual::run('get', 'sale_serviceaccount_Report_render-pdf', [
            'id'      => $id,
            'details' => $params['show_details'] ?? true,
            'logs'    => $params['show_logs'] ?? false,
            'view_id' => $params['view_id'] ?? 'print.default'
        ]);
    }

    /**
     * Generate an HTML version of a Report, intended for printing.
     *
     * @param int   $id     Identifier of the report to print.
     * @param array $params Accepted params are: show_details, show_logs and view_id.
     */
    public static function generateHtml($id, $params=[]) {
        $result = null;

        $report = self::id($id)
            ->read([
                'id',
                'created',
                'date',
                'status',
                'date_from',
                'name',
                'total_points',
                'total_credits',
                'balance_old',
                'balance_new',
                'service_account_id' => [
                    'id',
                    'name',
                    'contactName',
                    'customer_id'   => ['name', 'customer_external_ref', 'ref_account'],
                    'sa_type_id'    => ['name']
                ],
                'service_account_entries_ids' => [
                    'date',
                    'start',
                    'end',
                    'pause',
                    'on_site',
                    'description',
                    'has_ticket',
                    'has_task',
                    'taskID',
                    'taskNumber',
                    'taskDescription',
                    'ticketID',
                    'ticketNumber',
                    'ticketDescription',
                    'contact',
                    'points',
                    'calculation_log',
                    'sa_line_type_id'   => ['name'],
                    'creator'           => ['firstname', 'lastname'],
                    'employee_id'       => ['name', 'partner_identity_id' => ['firstname', 'lastname']],
                    'role_id'           => ['name']
                ]
            ])
            ->first();

        // retrieve target timezone : printed dates are intended to use local time)
        $tz = new \DateTimeZone("Europe/Brussels");
        // load image to embed to PDF reports
        $img_path = EQ_BASEDIR.'/packages/sale/views/serviceaccount/logo_netika_sm.png';
        // fallback to empty image
        $img_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=';

        if(file_exists($img_path)) {
            $img_data = file_get_contents($img_path);
            if($img_data !== false) {
                $img_url = 'data:image/png;base64,'.base64_encode($img_data);
            }
        }

        try {
            // create a map associating tickets and credits with their related lines
            $map_tt_lines = [];
            $map_cc_lines = [];

            // group all lines by ticketID or taskID
            foreach($report['service_account_entries_ids'] as $line) {
                // line relates to a Task or a Ticket
                if($line['has_ticket'] || $line['has_task']) {

                    $parent_id = ($line['has_ticket'])?'ticket-'.$line['ticketID']:'task-'.$line['taskID'];
                    $description = ($line['has_ticket'])?$line['ticketDescription']:$line['taskDescription'];
                    // if no title nor description was received, fallback to TT number
                    if(strlen($description) <= 0) {
                        $description = ($line['has_ticket'])?'Ticket '.$line['ticketNumber']:'Task '.$line['taskNumber'];
                    }

                    if(!isset($map_tt_lines[$parent_id])) {
                        $map_tt_lines[$parent_id] = [
                            // make sure ticket title/description takes less than one line
                            'description'   => substr($description, 0, 175),
                            'contact'       => $line['contact'],
                            'lines'         => []
                        ];
                    }
                    else {
                        if(strlen($map_tt_lines[$parent_id]['contact']) <= 0 && strlen($line['contact']) > 0) {
                            $map_tt_lines[$parent_id]['contact'] = $line['contact'];
                        }
                        if(strlen($map_tt_lines[$parent_id]['description']) <= 0 && strlen($description) > 0) {
                            $map_tt_lines[$parent_id]['description'] = $description;
                        }
                    }

                    // timezone offset in seconds to apply, depending on the date of the time entry
                    $tz_offset = $tz->getOffset(new \DateTime('@'.$line['start']));

                    $firstname = $line['employee_id']['partner_identity_id']['firstname'] ?? '';
                    $lastname = $line['employee_id']['partner_identity_id']['lastname'] ?? '';
                    $who = trim($firstname.' '.(strlen($lastname) ? substr($lastname, 0, 1).'.' : ''));

                    // adapt line and add to map
                    $map_tt_lines[$parent_id]['lines'][] = [
                        'date'          => date('d/m/Y', $line['date']),
                        'start'         => self::computeStringFromTime($line['start'] + $tz_offset - strtotime('midnight', $line['start'])),
                        'end'           => self::computeStringFromTime($line['end'] + $tz_offset - strtotime('midnight', $line['end'])),
                        'pause'         => self::computeStringFromTime(floor(abs($line['pause']) * 60) * 60),
                        'on_site'       => ($line['on_site'])?'Yes':'No',
                        'who'           => $who,
                        'role'          => $line['role_id']['name'] ?? '',
                        'type'          => $line['sa_line_type_id']['name'] ?? '',
                        'points'        => number_format((float) round(-$line['points'], 2), 2, '.', ''),
                        'description'   => ucfirst(substr(str_replace('<br />', ' ; ', strip_tags($line['description'])), 0, 370)),
                        'log'           => str_replace('<br />', ' ; ', $line['calculation_log'])
                    ];
                }
                // line relates to a Credit or a Correction
                else {
                    $firstname = $line['creator']['firstname'] ?? '';
                    $lastname = $line['creator']['lastname'] ?? '';
                    $who = trim($firstname.' '.(strlen($lastname) ? substr($lastname, 0, 1).'.' : ''));
                    $map_cc_lines[] = [
                        'date'          => date('d/m/Y', $line['date']),
                        'who'           => $who,
                        'description'   => ucfirst(substr(str_replace(['<p>', '</p>', '<br />'], ['', '', ' ; '], $line['description']), 0, 128)),
                        'points'        => number_format((float) round($line['points'], 2), 2, '.', '')
                    ];
                }
            }

            // compose the associative array to feed the template with
            $service_account_label = $report['service_account_id']['name'] ?? '';

            $customer = $report['service_account_id']['customer_id'] ?? null;
            $customer_name = $customer['name'] ?? '';
            $customer_ref = $customer['customer_external_ref'] ?? ($customer['ref_account'] ?? '');
            $customer_label = $customer_name.(strlen($customer_ref) ? ' ['.$customer_ref.']' : '');

            $values = [
                'name'              => $report['name'],
                'service_account'   => $service_account_label,
                'balance_old'       => (($report['balance_old'] >= 0)?'+':'').number_format((float) round($report['balance_old'], 2), 2, '.', ''),
                'balance_new'       => (($report['balance_new'] >= 0)?'+':'').number_format((float) round($report['balance_new'], 2), 2, '.', ''),
                'customer'          => $customer_label,
                'account_type'      => $report['service_account_id']['sa_type_id']['name'] ?? '',
                'emission_date'     => date("d/m/Y", $report['created']),
                'period'            => date("F Y", $report['date']),
                'period_dates'      => date("d/m/Y", $report['date_from']).' - '.date("d/m/Y", $report['date']),
                'tickets'           => $map_tt_lines,
                'credits'           => $map_cc_lines,
                'total_tickets'     => number_format((float) round($report['total_points'], 2), 2, '.', ''),
                'total_credits'     => (($report['total_credits'] >= 0)?'+':'').number_format((float) round($report['total_credits'], 2), 2, '.', ''),
                'show_details'      => (isset($params['show_details']))?((bool)$params['show_details']):true,
                'show_logs'         => (isset($params['show_logs']))?((bool)$params['show_logs']):false,
                'img_url'           => $img_url
            ];

            /*
                Inject all values into the template
            */

            try {
                $loader = new TwigFilesystemLoader(EQ_BASEDIR."/packages/sale/views/serviceaccount/");
                $twig = new TwigEnvironment($loader);
                $template = $twig->load('Report.'.($params['view_id'] ?? 'print.default').'.html');
                $html = $template->render($values);
            }
            catch(\Exception $e) {
                trigger_error("ORM::error while parsing template - ".$e->getMessage(), EQ_REPORT_DEBUG);
                throw new \Exception("template_parsing_issue", EQ_ERROR_INVALID_CONFIG);
            }

            $result = $html;
        }
        catch(\Exception $e) {
            trigger_error("ORM::unable to generate HTML Report - ".$e->getMessage(), EQ_REPORT_ERROR);
        }

        return $result;
    }

    private static function computeStringFromTime($value) {
        $hours = floor($value / 3600);
        $minutes = floor(($value % 3600) / 60);
        return sprintf("%02d:%02d", $hours, $minutes);
    }
}
