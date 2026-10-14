<?php
/**
 * PagHiper - Módulo oficial para integração com WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    3.1.2
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2026, PagHiper
 * @link       https://www.paghiper.com/
 */

use Illuminate\Database\Capsule\Manager as Capsule;

class PaghiperPdfInvoiceIntegrator {

    private $version,
            $activeTemplate,
            $parentTemplate = NULL,
            $tplPath = NULL;

    function __construct() {
        if (!defined('ROOTDIR')) {
            $initPath = __DIR__ . '/../../../../../init.php';
            if (file_exists($initPath)) {
                require_once($initPath);
            }
        }
    }

    public function autoHeal() {
        $this->updatePdfInvoiceTpl();
    }

    public function isTplIntegrated($tplFilePath = null) {
        $target_include = '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php';

        if ($tplFilePath === null) {
            $tplFilePath = $this->getPdfInvoiceTplPath();
        }

        if (!$tplFilePath || !file_exists($tplFilePath)) {
            return false;
        }

        $code = file_get_contents($tplFilePath);
        
        // Verifica se a string do include existe no código
        if (strpos($code, $target_include) !== false) {
            return true;
        }

        return false;
    }

    public function getPdfInvoiceTplPath($forceTemplateName = null) {
        if($this->tplPath && !$forceTemplateName)
            return $this->tplPath;

        $paths = [];

        if ($forceTemplateName) {
            $paths[] = ROOTDIR . "/templates/{$forceTemplateName}/invoicepdf.tpl";
        } else {
            $this->version = Capsule::table('tblconfiguration')->where('setting', 'Version')->value('value');
            $this->activeTemplate = Capsule::table('tblconfiguration')->where('setting', 'Template')->value('value');

            $paths[] = ROOTDIR . "/templates/{$this->activeTemplate}/invoicepdf.tpl";

            $templateConfig = ROOTDIR . "/templates/{$this->activeTemplate}/theme.yaml";
            if (file_exists($templateConfig)) {
                $yamlContent = file_get_contents($templateConfig);
                if (preg_match('/parent:\s*["\\]?([^"\\]+)["\\]?/', $yamlContent, $matches)) {
                    $this->parentTemplate = trim($matches[1]);
                    $paths[] = ROOTDIR . "/templates/{$this->parentTemplate}/invoicepdf.tpl";
                }
            }

            $isModern = version_compare($this->version, '8.1.0', '>=');
            $paths[] = ROOTDIR . ($isModern ? "/templates/twenty-one/invoicepdf.tpl" : "/templates/six/invoicepdf.tpl");
            $paths[] = ROOTDIR . "/templates/six/invoicepdf.tpl"; 
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->tplPath = $path;
                return $path;
            }
        }

        return null;
    }

    public function generateFileHash($file) {
        if (file_exists($file)) {
            return md5_file($file);
        }
        return md5($file);
    }

    public function updatePdfInvoiceTpl($forceTemplateName = null) {
        $tplFilePath = $this->getPdfInvoiceTplPath($forceTemplateName);

        if (!$tplFilePath || !file_exists($tplFilePath)) {
            return false;
        }

        if ($this->isTplIntegrated($tplFilePath)) {
            return true;
        }

        $localTime = time();
        $tplBackupPath = dirname($tplFilePath) . "/invoicepdf_backup_{$localTime}.tpl";

        if (!copy($tplFilePath, $tplBackupPath)) {
             logActivity("PagHiper: Erro ao realizar backup de invoicepdf.tpl antes da integração. Integração abortada.");
             return false; 
        }

        if (!is_writable($tplFilePath)) {
             logActivity("PagHiper: O arquivo invoicepdf.tpl não tem permissão de escrita. Integração abortada.");
             return false;
        }

        $code = file_get_contents($tplFilePath);
        $full_path = '/../../modules/gateways/paghiper/inc/helpers/attach_pdf_slip.php';
        
        // Inserção Limpa: Encontra a primeira tag <?php e insere o include logo após ela.
        // Isso preserva 100% da formatação e comentários originais.
        $includeStmt = "\n    // PagHiper - Anexo de Boleto e PIX\n    include(__DIR__ . '" . $full_path . "');\n";
        
        $newCode = preg_replace('/<\?php\s*/', "<?php" . $includeStmt, $code, 1);

        if ($newCode === $code) {
             // Regex falhou, talvez a tag <?php esteja escrita de forma diferente ou não exista.
             logActivity("PagHiper: Não foi possível injetar o código no invoicepdf.tpl. Tag <?php não encontrada.");
             return false;
        }

        $originalFileHash = $this->generateFileHash($tplBackupPath);

        try {
            error_clear_last();
            $tplUpdate = file_put_contents($tplFilePath, $newCode);

            if ($tplUpdate === false) {
                $error = error_get_last();
                logActivity("PagHiper: Falha ao escrever arquivo invoicepdf.tpl. Erro: " . ($error['message'] ?? 'Desconhecido'));
                return false;
            } else {
                \WHMCS\Config\Setting::setValue('Paghiper_InvoicePdf_Origin_TplHash', $originalFileHash);
                $customFileHash = $this->generateFileHash($tplFilePath);
                \WHMCS\Config\Setting::setValue('Paghiper_InvoicePdf_Custom_TplHash', $customFileHash);
                
                $smarty = new \WHMCS\Smarty();
                $smarty->clearCompiledTemplate();
                
                logActivity("PagHiper: Integração ao arquivo invoicepdf.tpl realizada com sucesso.");
                return true;
            }
        } catch (Exception $e) {
            logActivity("PagHiper: Erro inesperado ao integrar invoicepdf.tpl: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna todos os templates disponíveis que possuem o arquivo invoicepdf.tpl
     */
    public function getAvailableTemplates() {
        $templatesDir = ROOTDIR . '/templates/';
        $available = [];
        if (is_dir($templatesDir)) {
            $dirs = array_diff(scandir($templatesDir), array('.', '..'));
            foreach ($dirs as $dir) {
                if (is_dir($templatesDir . $dir) && file_exists($templatesDir . $dir . '/invoicepdf.tpl')) {
                    $available[] = $dir;
                }
            }
        }
        return $available;
    }

    /**
     * Retorna lista de backups disponíveis para um template específico
     */
    public function getBackups($templateName) {
        $templateDir = ROOTDIR . "/templates/{$templateName}/";
        $backups = [];
        if (is_dir($templateDir)) {
            $files = glob($templateDir . "invoicepdf_backup_*.tpl");
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'date' => date("Y-m-d H:i:s", filemtime($file)),
                    'size' => filesize($file)
                ];
            }
        }
        // Ordena do mais recente pro mais antigo
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        return $backups;
    }

    /**
     * Restaura um backup específico
     */
    public function restoreBackup($templateName, $backupFilename) {
        $templateDir = ROOTDIR . "/templates/{$templateName}/";
        $backupPath = $templateDir . $backupFilename;
        $originalPath = $templateDir . "invoicepdf.tpl";

        if (file_exists($backupPath) && is_writable($originalPath)) {
            if (copy($backupPath, $originalPath)) {
                $smarty = new \WHMCS\Smarty();
                $smarty->clearCompiledTemplate();
                return true;
            }
        }
        return false;
    }

    /**
     * Resgata configurações de um gateway com fallback seguro caso inativo
     *
     * @param string $gateway_name
     * @return array
     */
    private function get_safe_gateway_vars($gateway_name) {
        if (!function_exists('getGatewayVariables')) {
            $gw_path = dirname(__DIR__, 5) . '/includes/gatewayfunctions.php';
            if (file_exists($gw_path)) {
                require_once $gw_path;
            }
        }

        if (function_exists('getGatewayVariables')) {
            try {
                $vars = getGatewayVariables($gateway_name);
                if (is_array($vars)) {
                    return $vars;
                }
            } catch (\Throwable $e) {}
        }

        return [];
    }

    public function getFriendlyNames() {
        $paghiper_vars = $this->get_safe_gateway_vars('paghiper');
        $pix_vars = $this->get_safe_gateway_vars('paghiper_pix');

        return [
            'paghiper'     => !empty($paghiper_vars['name']) ? $paghiper_vars['name'] : 'PagHiper',
            'paghiper_pix' => !empty($pix_vars['name']) ? $pix_vars['name'] : 'PagHiper PIX'
        ];
    }

    public function renderIntegrationUI($module_name) {
        $system_url = '';
        if (class_exists('\App')) {
            try {
                $system_url = rtrim(\App::getSystemUrl(), "/");
            } catch (\Throwable $e) {}
        }
        if (!$system_url && class_exists('\WHMCS\Config\Setting')) {
            try {
                $system_url = rtrim(\WHMCS\Config\Setting::getValue('SystemURL') ?? '', "/");
            } catch (\Throwable $e) {}
        }
        $js_url = ($system_url ? $system_url : '') . '/modules/gateways/paghiper/assets/js/admin_tabs.js';
        
        $active_template = 'six';
        try {
            if (class_exists('Illuminate\Database\Capsule\Manager')) {
                $active_template = Capsule::table('tblconfiguration')->where('setting', 'Template')->value('value') ?: 'six';
            }
        } catch (\Throwable $e) {}

        $templates = $this->getAvailableTemplates();
        if (!in_array($active_template, $templates)) $templates[] = $active_template;
        
        $is_integrated = $this->isTplIntegrated($this->getPdfInvoiceTplPath($active_template));
        
        $friendly_names = $this->getFriendlyNames();
        $names_json = htmlspecialchars(json_encode($friendly_names), ENT_QUOTES, 'UTF-8');
        
        $paghiper_config = $this->get_safe_gateway_vars('paghiper');
        $issue_all_billet = isset($paghiper_config['issue_all']) ? $paghiper_config['issue_all'] : '';
        
        $paghiper_pix_config = $this->get_safe_gateway_vars('paghiper_pix');
        $issue_all_pix = isset($paghiper_pix_config['issue_all']) ? $paghiper_pix_config['issue_all'] : '';
        
        $issue_all_config = [
            'paghiper'     => ($issue_all_billet == '1' || $issue_all_billet == 'on'),
            'paghiper_pix' => ($issue_all_pix == '1' || $issue_all_pix == 'on')
        ];
        $issue_all_json = htmlspecialchars(json_encode($issue_all_config), ENT_QUOTES, 'UTF-8');

        $html = "<div style=\"background:#f8f9fa; border:1px solid #ddd; padding:15px; border-radius:4px; max-width: 600px;\" data-module=\"{$module_name}\" data-friendly-names=\"{$names_json}\" data-issue-all=\"{$issue_all_json}\" class=\"paghiper-integration-ui-container\">";
        $html .= '<h4>Gerenciamento da Integração de Boleto/PIX no PDF</h4>';
        $html .= '<p>O módulo precisa adicionar uma linha de código ao arquivo <code>invoicepdf.tpl</code> do seu tema para poder anexar boletos e PIX aos e-mails enviados aos clientes.</p>';
        
        $html .= '<div style="margin-bottom:15px;"><strong>Status: </strong> <span id="paghiper-int-status" class="paghiper-int-status">';
        $html .= $is_integrated ? '<span style="color:green;font-weight:bold;">Integrado</span>' : '<span style="color:red;font-weight:bold;">Não Integrado</span>';
        $html .= '</span></div>';
        
        $html .= '<div class="form-group"><label>Template Alvo:</label><br>';
        $html .= '<select id="paghiper-template-selector" class="form-control paghiper-template-selector" style="max-width: 300px; display:inline-block;">';
        foreach ($templates as $tpl) {
            $sel = ($tpl == $active_template) ? 'selected' : '';
            $html .= "<option value=\"{$tpl}\" {$sel}>{$tpl} " . (($tpl == $active_template) ? '(Ativo)' : '') . "</option>";
        }
        $html .= '</select>';
        $html .= ' <button type="button" id="paghiper-force-integration" class="btn btn-primary btn-sm paghiper-force-integration">Integrar</button></div>';
        
        $html .= '<div style="margin-top: 10px;">';
        $html .= '<label style="font-weight:normal; font-size:12px; color:#555;">';
        $html .= '<input type="checkbox" id="paghiper-custom-auto-pdf" class="paghiper-custom-auto-pdf"> <strong style=" font-size:14px;">Customização Automática do PDF (Auto-Heal)</strong><br> Se marcado, o sistema verificará silenciosamente se o template PDF possui o bloco do PagHiper antes do envio de cada fatura, e caso o tema da sua instalação seja atualizado ou trocado, o sistema atualizará a integração automaticamente.';
        $html .= '</label></div>';
        
        $html .= '<hr>';
        
        $backups = $this->getBackups($active_template);
        $html .= '<div class="form-group"><label>Restaurar Backup:</label><br>';
        $html .= '<select id="paghiper-backup-selector" class="form-control paghiper-backup-selector" style="max-width: 300px; display:inline-block;">';
        if (empty($backups)) {
            $html .= '<option value="">Nenhum backup disponível</option>';
        } else {
            foreach ($backups as $b) {
                $html .= "<option value=\"{$b['filename']}\">{$b['filename']} ({$b['date']})</option>";
            }
        }
        $html .= '</select>';
        $disabled = empty($backups) ? 'disabled' : '';
        $html .= ' <button type="button" id="paghiper-restore-backup" class="btn btn-danger btn-sm paghiper-restore-backup" ' . $disabled . '>Restaurar</button></div>';
        $html .= '</div>';
        $html .= "<script src=\"{$js_url}?v=" . time() . "\"></script>";
        
        return $html;
    }

    public static function handleAjaxActions() {
        if (isset($_POST['paghiper_action'])) {
            $action = $_POST['paghiper_action'];
            if (in_array($action, ['get_status', 'force_integration', 'restore_backup'])) {
                ob_clean();
                header('Content-Type: application/json');
                $integrator = new self();
                $template = $_POST['template'] ?? '';
                
                if ($action == 'get_status') {
                    $isIntegrated = $integrator->isTplIntegrated($integrator->getPdfInvoiceTplPath($template));
                    $backups = $integrator->getBackups($template);
                    echo json_encode(['status' => true, 'integrated' => $isIntegrated, 'backups' => $backups]);
                    exit;
                } elseif ($action == 'force_integration') {
                    $result = $integrator->updatePdfInvoiceTpl($template);
                    echo json_encode(['success' => $result, 'error' => $result ? '' : 'Falha na integração. Verifique se o arquivo tem permissão de escrita.']);
                    exit;
                } elseif ($action == 'restore_backup') {
                    $backup = $_POST['backup'] ?? '';
                    $result = $integrator->restoreBackup($template, $backup);
                    echo json_encode(['success' => $result, 'error' => $result ? '' : 'Falha ao restaurar backup. Verifique permissões.']);
                    exit;
                }
            }
        }
    }
}

