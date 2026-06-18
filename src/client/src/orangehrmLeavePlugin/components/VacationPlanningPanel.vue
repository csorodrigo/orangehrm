<template>
  <div class="ca-vacation-planning">
    <div class="ca-vacation-planning__header">
      <div>
        <oxd-text tag="h6" class="ca-vacation-planning__title">
          Planejamento de ferias
        </oxd-text>
        <oxd-text tag="p" class="ca-vacation-planning__subtitle">
          Judge CLT com preferencias, cargo e cobertura operacional
        </oxd-text>
      </div>
      <div class="ca-vacation-planning__score">
        <strong>{{ meta.aboveNineRate }}%</strong>
        <span>com nota acima de 9</span>
      </div>
    </div>

    <div v-if="isLoading" class="ca-vacation-planning__empty">
      Carregando planejamento...
    </div>
    <div v-else-if="isPermissionDenied" class="ca-vacation-planning__empty">
      Sem permissão para visualizar o planejamento de férias.
    </div>
    <div v-else-if="plans.length === 0" class="ca-vacation-planning__empty">
      Nenhum colaborador encontrado para planejar.
    </div>
    <template v-else>
      <div class="ca-vacation-planning__tabs" role="tablist">
        <button
          type="button"
          :class="{'--active': activeView === 'table'}"
          @click="activeView = 'table'"
        >
          Tabela
        </button>
        <button
          type="button"
          :class="{'--active': activeView === 'details'}"
          @click="activeView = 'details'"
        >
          Detalhes
        </button>
      </div>

      <div v-if="activeView === 'table'" class="ca-vacation-table">
        <div class="ca-vacation-table__head">
          <span>Colaborador</span>
          <span>Cargo</span>
          <span>Setor</span>
          <span>Data aprovada das ferias</span>
          <span>Preferencia atendida</span>
        </div>
        <div
          v-for="plan in visiblePlans"
          :key="`table-${plan.employee.empNumber}`"
          class="ca-vacation-table__row"
          :class="{'--selected': isSelected(plan)}"
          role="button"
          tabindex="0"
          @click="openPlanDetails(plan)"
          @keyup.enter="openPlanDetails(plan)"
          @keyup.space.prevent="openPlanDetails(plan)"
        >
          <strong>{{ plan.employee.name }}</strong>
          <span>{{ plan.employee.jobTitle || 'Cargo nao informado' }}</span>
          <span>{{ plan.employee.subunit || 'Setor nao informado' }}</span>
          <span class="ca-vacation-table__period">
            <strong>{{ recommendationStart(plan) }}</strong>
            <small>ate</small>
            <strong>{{ recommendationEnd(plan) }}</strong>
          </span>
          <span>
            <mark
              class="ca-vacation-table__preference"
              :class="tablePreferenceClass(plan)"
            >
              {{ preferenceLabel(plan) }}
            </mark>
          </span>
        </div>
      </div>

      <div v-else class="ca-vacation-planning__grid">
      <div
        v-for="plan in visiblePlans"
        :key="plan.employee.empNumber"
        class="ca-vacation-card"
      >
        <div class="ca-vacation-card__top">
          <div>
            <oxd-text tag="p" class="ca-vacation-card__name">
              {{ plan.employee.name }}
            </oxd-text>
            <oxd-text tag="p" class="ca-vacation-card__role">
              {{ plan.employee.jobTitle || 'Cargo nao informado' }}
            </oxd-text>
          </div>
          <span :class="scoreClass(plan.score)">Nota {{ plan.score }}</span>
        </div>

        <div class="ca-vacation-card__facts">
          <span>Admissao</span>
          <strong>{{ formatDate(plan.employee.joinedDate) }}</strong>
          <span>Vencimento maximo</span>
          <strong>{{ formatDate(plan.legal?.concessionEnd) }}</strong>
          <span>Preferencia atendida</span>
          <strong class="ca-vacation-card__preference-options">
            <span
              v-for="option in preferenceLetters(plan)"
              :key="option.label"
              class="ca-vacation-card__preference-option"
              :class="preferenceOptionClass(plan, option.label)"
              :aria-label="option.period"
              role="button"
              tabindex="0"
              @click.stop="openPreference(plan, option.label)"
              @keyup.enter.stop="openPreference(plan, option.label)"
              @keyup.space.stop.prevent="openPreference(plan, option.label)"
            >
              {{ option.label }}
              <span
                class="ca-vacation-card__preference-tooltip"
                :class="[
                  preferenceTooltipClass(option.label),
                  {'--editing': isEditing(plan, option.label)},
                ]"
                @click.stop
              >
                <span>{{ option.period }}</span>
                <span
                  v-if="isEditing(plan, option.label)"
                  class="ca-vacation-card__preference-editor"
                >
                  <label>
                    Inicio
                    <input
                      v-model="preferenceForm[preferenceFormKey(option.label)].fromDate"
                      type="date"
                    />
                  </label>
                  <label>
                    Fim
                    <input
                      v-model="preferenceForm[preferenceFormKey(option.label)].toDate"
                      type="date"
                    />
                  </label>
                  <span class="ca-vacation-card__preference-actions">
                    <button
                      type="button"
                      :disabled="isSavingPreference"
                      @click.stop="savePreference"
                    >
                      Salvar
                    </button>
                    <button
                      type="button"
                      :disabled="isSavingPreference"
                      @click.stop="closePreference"
                    >
                      Cancelar
                    </button>
                  </span>
                </span>
              </span>
            </span>
          </strong>
        </div>

        <div class="ca-vacation-card__approved-period">
          <span>Periodo admitido de ferias</span>
          <div>
            <strong>
              <small>Inicio</small>
              {{ recommendationStart(plan) }}
            </strong>
            <strong>
              <small>Fim</small>
              {{ recommendationEnd(plan) }}
            </strong>
          </div>
          <p v-if="acquisitionNotice(plan)">
            {{ acquisitionNotice(plan) }}
          </p>
        </div>

        <div class="ca-vacation-card__reasons">
          <span v-for="reason in visibleReasons(plan).slice(0, 3)" :key="reason">
            {{ reason }}
          </span>
        </div>
      </div>
      </div>
    </template>

    <div
      v-if="selectedPlan"
      class="ca-vacation-drawer__overlay"
      @click.self="closePlanDetails"
    >
      <aside class="ca-vacation-drawer" role="dialog" aria-modal="true">
        <div class="ca-vacation-drawer__header">
          <div>
            <strong>{{ selectedPlan.employee.name }}</strong>
            <span>
              {{ selectedPlan.employee.jobTitle || 'Cargo nao informado' }}
              - {{ selectedPlan.employee.subunit || 'Setor nao informado' }}
            </span>
          </div>
          <button
            type="button"
            aria-label="Fechar detalhes"
            @click="closePlanDetails"
          >
            x
          </button>
        </div>

        <div class="ca-vacation-drawer__section">
          <span :class="scoreClass(selectedPlan.score)">
            Nota {{ selectedPlan.score }}
          </span>
          <dl>
            <div>
              <dt>Admissao</dt>
              <dd>{{ formatDate(selectedPlan.employee.joinedDate) }}</dd>
            </div>
            <div>
              <dt>Vencimento maximo</dt>
              <dd>{{ formatDate(selectedPlan.legal?.concessionEnd) }}</dd>
            </div>
            <div>
              <dt>Preferencia atendida</dt>
              <dd>{{ preferenceLabel(selectedPlan) }}</dd>
            </div>
            <div>
              <dt>Restricao</dt>
              <dd>{{ restrictedMonthLabel(selectedPlan) }}</dd>
            </div>
          </dl>
        </div>

        <div class="ca-vacation-drawer__period">
          <span>Periodo admitido de ferias</span>
          <div>
            <strong>
              <small>Inicio</small>
              {{ recommendationStart(selectedPlan) }}
            </strong>
            <strong>
              <small>Fim</small>
              {{ recommendationEnd(selectedPlan) }}
            </strong>
          </div>
          <p v-if="acquisitionNotice(selectedPlan)">
            {{ acquisitionNotice(selectedPlan) }}
          </p>
        </div>

        <div class="ca-vacation-drawer__section">
          <h3>Preferencias preenchidas</h3>
          <div class="ca-vacation-drawer__preferences">
            <span
              v-for="option in preferenceLetters(selectedPlan)"
              :key="`drawer-${option.label}`"
              :class="preferenceOptionClass(selectedPlan, option.label)"
            >
              <strong>{{ option.label }}</strong>
              {{ option.period }}
            </span>
          </div>
        </div>

        <div class="ca-vacation-drawer__section">
          <h3>Restricoes e observacoes</h3>
          <div class="ca-vacation-drawer__reasons">
            <span
              v-for="reason in drawerReasons(selectedPlan)"
              :key="reason"
            >
              {{ reason }}
            </span>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';

const emptyPreferenceForm = () => ({
  optionA: {fromDate: null, toDate: null},
  optionB: {fromDate: null, toDate: null},
  optionC: {fromDate: null, toDate: null},
  restrictedMonth: null,
});

export default {
  name: 'VacationPlanningPanel',
  data() {
    return {
      isLoading: false,
      plans: [],
      isPermissionDenied: false,
      meta: {
        aboveNineRate: 0,
      },
      activeView: 'table',
      editingPlan: null,
      editingPreference: null,
      isSavingPreference: false,
      selectedPlan: null,
      preferenceForm: emptyPreferenceForm(),
      months: [
        {id: 1, label: 'Janeiro'},
        {id: 2, label: 'Fevereiro'},
        {id: 3, label: 'Marco'},
        {id: 4, label: 'Abril'},
        {id: 5, label: 'Maio'},
        {id: 6, label: 'Junho'},
        {id: 7, label: 'Julho'},
        {id: 8, label: 'Agosto'},
        {id: 9, label: 'Setembro'},
        {id: 10, label: 'Outubro'},
        {id: 11, label: 'Novembro'},
        {id: 12, label: 'Dezembro'},
      ],
    };
  },
  computed: {
    visiblePlans() {
      return this.plans;
    },
  },
  mounted() {
    this.loadPlanning();
    window.addEventListener(
      'ca:generate-vacation-planning-pdf',
      this.generateVacationPlanningPdf,
    );
  },
  unmounted() {
    window.removeEventListener(
      'ca:generate-vacation-planning-pdf',
      this.generateVacationPlanningPdf,
    );
  },
  methods: {
    loadPlanning() {
      this.isLoading = true;
      const http = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/leave/vacation-planning',
      );
      http.setIgnorePath('/api/v2/leave/vacation-planning');
      http
        .getAll()
        .then(({data}) => {
          this.isPermissionDenied = false;
          this.plans = data.data || [];
          this.meta = data.meta || {aboveNineRate: 0};
        })
        .catch((error) => {
          const status = error?.response?.status || error?.status;
          if (status === 403) {
            this.isPermissionDenied = true;
            this.plans = [];
            this.meta = {aboveNineRate: 0};
            return;
          }
          if (status === 422) {
            this.isPermissionDenied = false;
            this.plans = [];
            this.meta = {aboveNineRate: 0};
            return;
          }
          return Promise.reject(error);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    openPreference(plan, label) {
      if (this.isEditing(plan, label)) {
        this.closePreference();
        return;
      }

      this.editingPlan = plan;
      this.editingPreference = label;
      const form = emptyPreferenceForm();
      const options = plan.preferences?.options || [];
      options.forEach((option) => {
        if (option.label === 'A') form.optionA = this.copyOption(option);
        if (option.label === 'B') form.optionB = this.copyOption(option);
        if (option.label === 'C') form.optionC = this.copyOption(option);
      });
      form.restrictedMonth = plan.preferences?.restrictedMonth || null;
      this.preferenceForm = form;
    },
    closePreference() {
      this.editingPlan = null;
      this.editingPreference = null;
      this.preferenceForm = emptyPreferenceForm();
    },
    isEditing(plan, label = null) {
      return (
        this.editingPlan?.employee?.empNumber === plan.employee.empNumber &&
        (label === null || this.editingPreference === label)
      );
    },
    savePreference() {
      if (!this.editingPlan) return;
      this.isSavingPreference = true;
      const payload = {
        optionA: this.preferenceForm.optionA,
        optionB: this.preferenceForm.optionB,
        optionC: this.preferenceForm.optionC,
      };
      if (this.preferenceForm.restrictedMonth !== null) {
        payload.restrictedMonth = this.preferenceForm.restrictedMonth;
      }
      const http = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/leave/vacation-preferences',
      );
      http
        .update(this.editingPlan.employee.empNumber, payload)
        .then(() => {
          this.closePreference();
          this.loadPlanning();
        })
        .catch((error) => {
          if (error?.response?.status === 403) {
            this.isPermissionDenied = true;
            this.editingPlan = null;
            return;
          }
          return Promise.reject(error);
        })
        .finally(() => {
          this.isSavingPreference = false;
        });
    },
    preferenceFormKey(label) {
      return `option${label}`;
    },
    copyOption(option) {
      return {
        fromDate: option.fromDate,
        toDate: option.toDate,
      };
    },
    openPlanDetails(plan) {
      this.selectedPlan = plan;
    },
    closePlanDetails() {
      this.selectedPlan = null;
    },
    isSelected(plan) {
      return (
        this.selectedPlan?.employee?.empNumber === plan.employee.empNumber
      );
    },
    recommendationStart(plan) {
      return this.formatDate(plan.recommendation?.fromDate);
    },
    recommendationEnd(plan) {
      return this.formatDate(plan.recommendation?.toDate);
    },
    preferenceLabel(plan) {
      return plan.recommendation?.matchedPreference || 'Alternativa';
    },
    preferenceLetters(plan) {
      return ['A', 'B', 'C'].map((label) => {
        const option = (plan.preferences?.options || []).find(
          (preference) => preference.label === label,
        );
        return {
          label,
          period:
            option?.fromDate && option?.toDate
              ? `${this.formatDate(option.fromDate)} ate ${this.formatDate(option.toDate)}`
              : 'Preferencia nao cadastrada',
        };
      });
    },
    preferenceOptionClass(plan, label) {
      if (plan.recommendation?.matchedPreference !== label) {
        return '';
      }
      return `--matched-${label.toLowerCase()}`;
    },
    preferenceTooltipClass(label) {
      if (label === 'A') return '--from-left';
      if (label === 'C') return '--from-right';
      return '--from-top';
    },
    tablePreferenceClass(plan) {
      const label = plan.recommendation?.matchedPreference;
      if (['A', 'B', 'C'].includes(label)) {
        return `--matched-${label.toLowerCase()}`;
      }
      return '--alternative';
    },
    restrictedMonthLabel(plan) {
      const month = this.months.find(
        (item) => item.id === plan.preferences?.restrictedMonth,
      );
      return month ? month.label : 'Sem restricao';
    },
    drawerReasons(plan) {
      const reasons = [...(plan.reasons || [])];
      if (plan.preferences?.restrictedMonth) {
        reasons.unshift(
          `Mes restrito informado: ${this.restrictedMonthLabel(plan)}`,
        );
      }
      return reasons.length ? reasons : ['Sem restricoes adicionais'];
    },
    acquisitionNotice(plan) {
      return (
        plan.reasons?.find((reason) => reason.includes('12 meses')) || ''
      );
    },
    visibleReasons(plan) {
      return (plan.reasons || []).filter(
        (reason) => reason !== this.acquisitionNotice(plan),
      );
    },
    formatDate(date) {
      if (!date) return '-';
      const parts = String(date).split('-');
      if (parts.length !== 3) return date;
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    },
    scoreClass(score) {
      if (score >= 9) return 'ca-vacation-card__badge --good';
      if (score >= 8) return 'ca-vacation-card__badge --warn';
      return 'ca-vacation-card__badge --risk';
    },
    async generateVacationPlanningPdf() {
      if (this.plans.length === 0) return;

      const html = this.createVacationPlanningReportHtml();
      const parser = new DOMParser();
      const documentHtml = parser.parseFromString(html, 'text/html');
      const report = documentHtml.getElementById('vacation-report');
      const reportStyle = documentHtml.querySelector('style')?.textContent || '';

      if (!report) return;

      await this.loadHtml2PdfLibrary();

      const container = document.createElement('div');
      const style = document.createElement('style');
      style.textContent = reportStyle;
      container.style.position = 'fixed';
      container.style.top = '0';
      container.style.left = '0';
      container.style.width = '980px';
      container.style.pointerEvents = 'none';
      container.style.zIndex = '-1';
      container.style.background = '#ffffff';
      container.appendChild(report);
      document.body.appendChild(style);
      document.body.appendChild(container);

      const options = {
        margin: [8, 8, 8, 8],
        filename: 'planejamento-ferias.pdf',
        image: {type: 'jpeg', quality: 0.98},
        html2canvas: {scale: 2, useCORS: true, backgroundColor: '#ffffff'},
        jsPDF: {unit: 'mm', format: 'a4', orientation: 'landscape'},
        pagebreak: {mode: ['css', 'legacy']},
      };

      try {
        await window.html2pdf().set(options).from(report).save();
      } finally {
        container.remove();
        style.remove();
      }
    },
    loadHtml2PdfLibrary() {
      if (window.html2pdf) {
        return Promise.resolve();
      }

      return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = this.html2PdfLibraryUrl();
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
      });
    },
    html2PdfLibraryUrl() {
      return `${window.location.origin}${window.appGlobal.baseUrl.replace(
        /\/index\.php$/,
        '/dist',
      )}/vendor/html2pdf.bundle.min.js`;
    },
    createVacationPlanningReportHtml() {
      const generatedAt = new Date().toLocaleString('pt-BR');
      const libraryUrl = this.html2PdfLibraryUrl();
      const planningUrl = `${window.location.origin}${window.appGlobal.baseUrl}/leave/viewLeaveList`;
      const rows = this.plans.map((plan) => this.createReportRow(plan)).join('');
      const scriptOpen = '<scr' + 'ipt';
      const scriptClose = '</scr' + 'ipt>';

      return `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Planejamento de ferias</title>
  ${scriptOpen} src="${libraryUrl}">${scriptClose}
  <style>${this.createReportCss()}</style>
</head>
<body>
  <div class="report-actions">
    <button type="button" class="--secondary" onclick="goBackToSite()">Voltar ao site</button>
    <button type="button" class="--secondary" onclick="downloadHtml()">Baixar HTML</button>
    <button type="button" onclick="downloadPdf()">Gerar PDF</button>
  </div>
  <main id="vacation-report" class="report-page">
    <header class="report-header">
      <div>
        <p class="eyebrow">Planejamento operacional</p>
        <h1>Planejamento de ferias</h1>
        <p>Judge CLT com preferencias, cargo e cobertura operacional</p>
      </div>
      <div class="report-score">
        <strong>${this.escapeHtml(this.meta.aboveNineRate)}%</strong>
        <span>com nota acima de 9</span>
      </div>
    </header>
    <section class="report-meta">
      <span>Gerado em ${this.escapeHtml(generatedAt)}</span>
      <span>${this.escapeHtml(this.plans.length)} colaboradores analisados</span>
    </section>
    <section class="report-table">
      <div class="report-table__head">
        <span>Colaborador</span>
        <span>Cargo</span>
        <span>Setor</span>
        <span>Data aprovada das ferias</span>
        <span>Pref.</span>
      </div>
      ${rows}
    </section>
  </main>
  ${scriptOpen}>
    const planningUrl = ${JSON.stringify(planningUrl)};
    const reportHtml = document.documentElement.outerHTML;
    function goBackToSite() {
      window.location.href = planningUrl;
    }
    function downloadHtml() {
      const blob = new Blob([reportHtml], {type: 'text/html'});
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'planejamento-ferias.html';
      link.click();
      URL.revokeObjectURL(link.href);
    }
    function downloadPdf() {
      const element = document.getElementById('vacation-report');
      const options = {
        margin: [8, 8, 8, 8],
        filename: 'planejamento-ferias.pdf',
        image: {type: 'jpeg', quality: 0.98},
        html2canvas: {scale: 2, useCORS: true, backgroundColor: '#ffffff'},
        jsPDF: {unit: 'mm', format: 'a4', orientation: 'landscape'},
        pagebreak: {mode: ['css', 'legacy']}
      };
      window.html2pdf().set(options).from(element).save();
    }
  ${scriptClose}
</body>
</html>`;
    },
    createReportRow(plan) {
      const preference = this.preferenceLabel(plan);
      return `<div class="report-table__row">
        <strong>${this.escapeHtml(plan.employee.name)}</strong>
        <span>${this.escapeHtml(plan.employee.jobTitle || 'Cargo nao informado')}</span>
        <span>${this.escapeHtml(plan.employee.subunit || 'Setor nao informado')}</span>
        <span class="report-period">
          <strong>${this.escapeHtml(this.recommendationStart(plan))}</strong>
          <small>ate</small>
          <strong>${this.escapeHtml(this.recommendationEnd(plan))}</strong>
        </span>
        <span><mark class="${this.reportPreferenceClass(preference)}">${this.escapeHtml(preference)}</mark></span>
      </div>`;
    },
    createReportCss() {
      return `
        * { box-sizing: border-box; }
        body { margin: 0; background: #f6f5fb; color: #1d286f; font-family: "Nunito Sans", Arial, sans-serif; }
        .report-actions { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; padding: 14px 18px; background: #ffffff; border-bottom: 1px solid #e8eaef; }
        .report-actions button { min-height: 38px; padding: 0 18px; border: 0; border-radius: 19px; background: #1d286f; color: #ffffff; font-weight: 800; cursor: pointer; }
        .report-actions button.--secondary { background: #eef2fb; color: #1d286f; }
        .report-page { width: min(980px, calc(100vw - 32px)); margin: 18px auto; padding: 18px; background: #ffffff; border-radius: 8px; box-shadow: 0 8px 28px rgba(15, 35, 95, 0.10); overflow: hidden; }
        .report-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding-bottom: 14px; border-bottom: 1px solid #e8eaef; }
        .eyebrow { margin: 0 0 4px; color: #929baa; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        h1 { margin: 0; font-size: 26px; line-height: 1.1; color: #1d286f; }
        .report-header p { margin: 6px 0 0; color: #64728c; }
        .report-score { min-width: 120px; text-align: right; }
        .report-score strong { display: block; font-size: 30px; line-height: 1; }
        .report-score span { color: #64728c; font-size: 12px; }
        .report-meta { display: flex; justify-content: space-between; gap: 12px; margin: 14px 0; color: #64728c; font-size: 12px; font-weight: 700; }
        .report-table { border: 1px solid #e8eaef; border-radius: 8px; overflow: hidden; }
        .report-table__head, .report-table__row { display: grid; grid-template-columns: 1.35fr 1fr 0.82fr 0.92fr 0.34fr; align-items: stretch; }
        .report-table__head { background: #f6f5fb; color: #929baa; font-size: 10px; font-weight: 900; line-height: 1.15; text-transform: uppercase; }
        .report-table__row { min-height: 58px; border-top: 1px solid #e8eaef; color: #64728c; font-size: 11.5px; line-height: 1.18; }
        .report-table__head span, .report-table__row > * { display: flex; align-items: center; min-width: 0; padding: 8px 9px; border-left: 1px solid #e8eaef; overflow-wrap: anywhere; }
        .report-table__head span:first-child, .report-table__row > *:first-child { border-left: 0; }
        .report-table__row strong { color: #1d286f; }
        .report-period { display: grid !important; gap: 3px; align-content: center; justify-items: start; background: #eef6ff; }
        .report-period strong { padding: 3px 6px; border-radius: 5px; background: #ffffff; box-shadow: inset 0 0 0 1px #bcd7ff; }
        .report-period small { color: #929baa; font-size: 9px; font-weight: 900; text-transform: uppercase; }
        mark { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 36px; border-radius: 7px; font-weight: 900; }
        .pref-a { background: #dff5e8; color: #188a42; }
        .pref-b { background: #fff1cc; color: #956000; }
        .pref-c { background: #fde7ec; color: #b00020; }
        .pref-alt { background: #f6f5fb; color: #64728c; }
        @media print {
          body { background: #ffffff; }
          .report-actions { display: none; }
          .report-page { width: auto; margin: 0; box-shadow: none; border-radius: 0; }
        }
        @media (max-width: 760px) {
          .report-page { width: calc(100vw - 16px); margin: 12px auto; padding: 12px; }
          .report-header { display: block; }
          .report-score { margin-top: 12px; text-align: left; }
          .report-table__head, .report-table__row { grid-template-columns: minmax(108px, 1.1fr) minmax(92px, 0.9fr) minmax(82px, 0.75fr) minmax(96px, 0.95fr) minmax(52px, 0.45fr); }
          .report-table__head { font-size: 9px; }
          .report-table__row { font-size: 11px; }
          .report-table__head span, .report-table__row > * { padding: 9px 7px; }
          .report-period strong { padding: 3px 5px; }
          mark { min-height: 36px; }
        }
      `;
    },
    reportPreferenceClass(preference) {
      if (preference === 'A') return 'pref-a';
      if (preference === 'B') return 'pref-b';
      if (preference === 'C') return 'pref-c';
      return 'pref-alt';
    },
    reportScoreClass(score) {
      if (score >= 9) return 'score-good';
      if (score >= 8) return 'score-warn';
      return 'score-risk';
    },
    escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },
  },
};
</script>

<style lang="scss" scoped>
.ca-vacation-planning {
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  background: $oxd-white-color;
  border-radius: 0.5rem;
  box-shadow: 0 4px 16px rgba(15, 35, 95, 0.08);
}

.ca-vacation-planning__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.ca-vacation-planning__title {
  color: $oxd-primary-one-color;
  font-weight: 700;
}

.ca-vacation-planning__subtitle {
  margin-top: 0.25rem;
  color: $oxd-interface-gray-color;
  font-size: 0.85rem;
}

.ca-vacation-planning__score {
  min-width: 9rem;
  text-align: right;
  color: $oxd-primary-one-color;
}

.ca-vacation-planning__score strong {
  display: block;
  font-size: 1.4rem;
}

.ca-vacation-planning__score span {
  font-size: 0.75rem;
  color: $oxd-interface-gray-color;
}

.ca-vacation-planning__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr));
  gap: 0.75rem;
}

.ca-vacation-planning__empty {
  padding: 1.5rem;
  text-align: center;
  color: $oxd-interface-gray-color;
}

.ca-vacation-planning__tabs {
  display: inline-grid;
  grid-template-columns: repeat(2, minmax(7rem, 1fr));
  gap: 0.25rem;
  padding: 0.25rem;
  margin-bottom: 1rem;
  border-radius: 0.5rem;
  background: $oxd-background-pastel-white-color;
}

.ca-vacation-planning__tabs button {
  min-height: 2.25rem;
  border: 0;
  border-radius: 0.4rem;
  background: transparent;
  color: $oxd-interface-gray-color;
  font-weight: 700;
  cursor: pointer;
}

.ca-vacation-planning__tabs button.--active {
  background: $oxd-primary-one-color;
  color: $oxd-white-color;
  box-shadow: 0 3px 10px rgba(15, 35, 95, 0.16);
}

.ca-vacation-table {
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 0.5rem;
  background: $oxd-white-color;
}

.ca-vacation-table__head,
.ca-vacation-table__row {
  display: grid;
  grid-template-columns: minmax(7.5rem, 1.35fr) minmax(0, 1fr) minmax(0, 0.9fr) minmax(7.5rem, 1.2fr) minmax(4.8rem, 0.7fr);
  gap: 0;
  align-items: center;
}

.ca-vacation-table__head {
  background: $oxd-background-pastel-white-color;
  color: $oxd-interface-gray-color;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
}

.ca-vacation-table__row {
  min-height: 4.1rem;
  border-top: 1px solid $oxd-interface-gray-lighten-2-color;
  color: $oxd-interface-gray-darken-1-color;
  font-size: 0.78rem;
  cursor: pointer;
  transition: background-color 0.15s ease, box-shadow 0.15s ease;
}

.ca-vacation-table__row:hover,
.ca-vacation-table__row:focus {
  background: #f7faff;
  outline: 0;
}

.ca-vacation-table__row.--selected {
  box-shadow: inset 0 0 0 2px #bcd7ff;
}

.ca-vacation-table__head span,
.ca-vacation-table__row > * {
  box-sizing: border-box;
  display: flex;
  align-items: center;
  min-width: 0;
  min-height: 100%;
  padding: 0.8rem;
  border-left: 1px solid $oxd-interface-gray-lighten-2-color;
  overflow-wrap: anywhere;
}

.ca-vacation-table__head span:first-child,
.ca-vacation-table__row > *:first-child {
  border-left: 0;
}

.ca-vacation-table__row > span:last-child {
  justify-content: center;
  align-self: stretch;
  padding: 0.18rem;
}

.ca-vacation-table__row strong,
.ca-vacation-table__row span {
  min-width: 0;
  overflow-wrap: anywhere;
}

.ca-vacation-table__row strong {
  color: $oxd-primary-one-color;
}

.ca-vacation-table__period {
  display: grid;
  gap: 0.2rem;
  align-content: center;
  justify-items: start;
  background: #eef6ff;
}

.ca-vacation-table__period strong {
  padding: 0.18rem 0.45rem;
  border-radius: 0.35rem;
  background: $oxd-white-color;
  color: $oxd-primary-one-color;
  font-size: 0.85rem;
  font-weight: 800;
  line-height: 1.2;
  box-shadow: inset 0 0 0 1px #bcd7ff;
}

.ca-vacation-table__period small {
  color: $oxd-interface-gray-color;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
}

.ca-vacation-table__preference {
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  min-width: 0;
  min-height: 2.35rem;
  padding: 0.35rem;
  border-radius: 0.45rem;
  font-size: 0.82rem;
  font-weight: 800;
  line-height: 1.2;
  border: 1px solid transparent;
  background: $oxd-background-pastel-white-color;
  color: $oxd-interface-gray-color;
}

.ca-vacation-table__preference.--matched-a {
  border-color: #bce9cc;
  background: #dff5e8;
  color: #188a42;
}

.ca-vacation-table__preference.--matched-b {
  border-color: #ffe4a6;
  background: #fff1cc;
  color: #956000;
}

.ca-vacation-table__preference.--matched-c {
  border-color: #f7bdc8;
  background: #fde7ec;
  color: #b00020;
}

.ca-vacation-table__preference.--alternative {
  border-color: $oxd-interface-gray-lighten-2-color;
  background: $oxd-background-pastel-white-color;
}

.ca-vacation-card {
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 0.5rem;
  padding: 1rem;
  background: #ffffff;
}

.ca-vacation-card__top {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.8rem;
}

.ca-vacation-card__name {
  font-weight: 700;
  color: $oxd-primary-one-color;
}

.ca-vacation-card__role {
  color: $oxd-interface-gray-color;
  font-size: 0.78rem;
}

.ca-vacation-card__badge {
  align-self: flex-start;
  padding: 0.25rem 0.55rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  white-space: nowrap;
}

.ca-vacation-card__badge.--good {
  color: #188a42;
  background: #e7f7ee;
}

.ca-vacation-card__badge.--warn {
  color: #956000;
  background: #fff3d6;
}

.ca-vacation-card__badge.--risk {
  color: #b00020;
  background: #fde7ec;
}

.ca-vacation-card__facts {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 0.35rem 0.75rem;
  font-size: 0.78rem;
}

.ca-vacation-card__facts span {
  color: $oxd-interface-gray-color;
}

.ca-vacation-card__facts strong {
  color: $oxd-interface-gray-darken-1-color;
}

.ca-vacation-card__preference-options {
  display: inline-flex;
  gap: 0.35rem;
  align-items: center;
}

.ca-vacation-card__preference-option {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.55rem;
  height: 1.55rem;
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 50%;
  background: $oxd-background-pastel-white-color;
  color: $oxd-interface-gray-color;
  font-size: 0.78rem;
  font-weight: 800;
  cursor: pointer;
}

.ca-vacation-card__preference-tooltip {
  position: absolute;
  z-index: 5;
  bottom: calc(100% + 0.55rem);
  left: 50%;
  width: max-content;
  max-width: 11rem;
  padding: 0.45rem 0.6rem;
  border-radius: 0.35rem;
  background: $oxd-interface-gray-darken-2-color;
  color: $oxd-white-color;
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 1.25;
  text-align: center;
  white-space: normal;
  box-shadow: 0 0.25rem 0.75rem rgba(15, 35, 95, 0.2);
  opacity: 0;
  pointer-events: none;
  transform: translate(-50%, 0.2rem);
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.ca-vacation-card__preference-tooltip.--editing {
  display: grid;
  gap: 0.55rem;
  min-width: 14rem;
  max-width: 15rem;
  padding: 0.65rem;
  opacity: 1;
  pointer-events: auto;
}

.ca-vacation-card__facts .ca-vacation-card__preference-tooltip {
  color: $oxd-white-color;
}

.ca-vacation-card__preference-editor {
  display: grid;
  gap: 0.45rem;
  text-align: left;
}

.ca-vacation-card__preference-editor label {
  display: grid;
  gap: 0.25rem;
  color: $oxd-white-color;
  font-size: 0.68rem;
  font-weight: 700;
}

.ca-vacation-card__preference-editor input {
  min-height: 2rem;
  padding: 0 0.45rem;
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 0.35rem;
  background: $oxd-white-color;
  color: $oxd-primary-one-color;
  font-weight: 700;
}

.ca-vacation-card__preference-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
}

.ca-vacation-card__preference-actions button {
  min-height: 1.9rem;
  border: 0;
  border-radius: 0.35rem;
  background: $oxd-white-color;
  color: $oxd-primary-one-color;
  font-weight: 800;
  cursor: pointer;
}

.ca-vacation-card__preference-actions button:last-child {
  background: rgba(255, 255, 255, 0.16);
  color: $oxd-white-color;
}

.ca-vacation-card__preference-actions button:disabled {
  cursor: default;
  opacity: 0.65;
}

.ca-vacation-card__preference-tooltip::after {
  position: absolute;
  top: 100%;
  left: 50%;
  width: 0;
  height: 0;
  border: 0.35rem solid transparent;
  border-top-color: $oxd-interface-gray-darken-2-color;
  content: '';
  transform: translateX(-50%);
}

.ca-vacation-card__preference-tooltip.--from-left {
  right: calc(100% + 0.55rem);
  bottom: 50%;
  left: auto;
  transform: translate(-0.2rem, 50%);
}

.ca-vacation-card__preference-tooltip.--from-left::after {
  top: 50%;
  left: 100%;
  border-top-color: transparent;
  border-left-color: $oxd-interface-gray-darken-2-color;
  transform: translateY(-50%);
}

.ca-vacation-card__preference-tooltip.--from-right {
  bottom: 50%;
  left: calc(100% + 0.55rem);
  transform: translate(0.2rem, 50%);
}

.ca-vacation-card__preference-tooltip.--from-right::after {
  top: 50%;
  right: 100%;
  left: auto;
  border-top-color: transparent;
  border-right-color: $oxd-interface-gray-darken-2-color;
  transform: translateY(-50%);
}

.ca-vacation-card__preference-option:hover
  .ca-vacation-card__preference-tooltip,
.ca-vacation-card__preference-option:focus
  .ca-vacation-card__preference-tooltip,
.ca-vacation-card__preference-tooltip.--editing {
  opacity: 1;
  transform: translate(-50%, 0);
}

.ca-vacation-card__preference-option:hover
  .ca-vacation-card__preference-tooltip.--from-left,
.ca-vacation-card__preference-option:focus
  .ca-vacation-card__preference-tooltip.--from-left,
.ca-vacation-card__preference-option:hover
  .ca-vacation-card__preference-tooltip.--from-right,
.ca-vacation-card__preference-option:focus
  .ca-vacation-card__preference-tooltip.--from-right,
.ca-vacation-card__preference-tooltip.--editing.--from-left,
.ca-vacation-card__preference-tooltip.--editing.--from-right {
  transform: translate(0, 50%);
}

.ca-vacation-card__preference-option.--matched-a {
  border-color: #92d8ad;
  background: #e7f7ee;
  color: #188a42;
}

.ca-vacation-card__preference-option.--matched-b {
  border-color: #ffd77a;
  background: #fff3d6;
  color: #956000;
}

.ca-vacation-card__preference-option.--matched-c {
  border-color: #f5a9b6;
  background: #fde7ec;
  color: #b00020;
}

.ca-vacation-drawer__overlay {
  position: fixed;
  z-index: 1200;
  inset: 0;
  display: flex;
  justify-content: flex-end;
  background: rgba(15, 35, 95, 0.24);
}

.ca-vacation-drawer {
  box-sizing: border-box;
  width: min(28rem, calc(100vw - 2rem));
  height: 100vh;
  padding: 1.2rem;
  overflow-y: auto;
  background: $oxd-white-color;
  box-shadow: -0.4rem 0 1.4rem rgba(15, 35, 95, 0.16);
  animation: ca-vacation-drawer-in 0.18s ease-out;
}

.ca-vacation-drawer__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.9rem;
  border-bottom: 1px solid $oxd-interface-gray-lighten-2-color;
}

.ca-vacation-drawer__header div {
  display: grid;
  gap: 0.25rem;
}

.ca-vacation-drawer__header strong {
  color: $oxd-primary-one-color;
  font-size: 1rem;
}

.ca-vacation-drawer__header span {
  color: $oxd-interface-gray-color;
  font-size: 0.78rem;
}

.ca-vacation-drawer__header button {
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 50%;
  background: $oxd-background-pastel-white-color;
  color: $oxd-interface-gray-darken-1-color;
  font-weight: 800;
  cursor: pointer;
}

.ca-vacation-drawer__section {
  display: grid;
  gap: 0.75rem;
  margin-top: 1rem;
}

.ca-vacation-drawer__section h3 {
  margin: 0;
  color: $oxd-primary-one-color;
  font-size: 0.82rem;
}

.ca-vacation-drawer__section dl {
  display: grid;
  gap: 0.55rem;
  margin: 0;
}

.ca-vacation-drawer__section dl div {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.55rem 0.65rem;
  border-radius: 0.4rem;
  background: $oxd-background-pastel-white-color;
}

.ca-vacation-drawer__section dt {
  color: $oxd-interface-gray-color;
  font-size: 0.75rem;
}

.ca-vacation-drawer__section dd {
  margin: 0;
  color: $oxd-primary-one-color;
  font-size: 0.78rem;
  font-weight: 700;
  text-align: right;
}

.ca-vacation-drawer__period {
  margin-top: 1rem;
  padding: 0.75rem;
  border: 1px solid #bcd7ff;
  border-radius: 0.5rem;
  background: #eef6ff;
}

.ca-vacation-drawer__period > span {
  display: block;
  margin-bottom: 0.55rem;
  color: $oxd-primary-one-color;
  font-size: 0.78rem;
  font-weight: 700;
}

.ca-vacation-drawer__period > div {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
}

.ca-vacation-drawer__period strong {
  display: grid;
  gap: 0.15rem;
  min-width: 0;
  padding: 0.6rem;
  border-radius: 0.4rem;
  background: $oxd-white-color;
  color: $oxd-primary-one-color;
  font-size: 0.95rem;
}

.ca-vacation-drawer__period small {
  color: $oxd-interface-gray-color;
  font-size: 0.7rem;
  font-weight: 600;
}

.ca-vacation-drawer__period p {
  margin: 0.65rem 0 0;
  padding: 0.55rem;
  border-radius: 0.4rem;
  background: #fff7df;
  color: #956000;
  font-size: 0.75rem;
  font-weight: 700;
}

.ca-vacation-drawer__preferences,
.ca-vacation-drawer__reasons {
  display: grid;
  gap: 0.5rem;
}

.ca-vacation-drawer__preferences span,
.ca-vacation-drawer__reasons span {
  padding: 0.55rem 0.65rem;
  border-radius: 0.4rem;
  background: $oxd-background-pastel-white-color;
  color: $oxd-interface-gray-darken-1-color;
  font-size: 0.76rem;
}

.ca-vacation-drawer__preferences span {
  display: grid;
  grid-template-columns: 1.75rem 1fr;
  align-items: center;
  gap: 0.55rem;
}

.ca-vacation-drawer__preferences span.--matched-a {
  background: #e7f7ee;
  color: #188a42;
}

.ca-vacation-drawer__preferences span.--matched-b {
  background: #fff3d6;
  color: #956000;
}

.ca-vacation-drawer__preferences span.--matched-c {
  background: #fde7ec;
  color: #b00020;
}

.ca-vacation-drawer__preferences strong {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.55rem;
  height: 1.55rem;
  border-radius: 50%;
  background: $oxd-white-color;
}

@keyframes ca-vacation-drawer-in {
  from {
    transform: translateX(100%);
  }

  to {
    transform: translateX(0);
  }
}

.ca-vacation-card__approved-period {
  margin-top: 0.85rem;
  padding: 0.75rem;
  border: 1px solid #bcd7ff;
  border-radius: 0.5rem;
  background: #eef6ff;
}

.ca-vacation-card__approved-period > span {
  display: block;
  margin-bottom: 0.5rem;
  color: $oxd-primary-one-color;
  font-size: 0.78rem;
  font-weight: 700;
}

.ca-vacation-card__approved-period > div {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
}

.ca-vacation-card__approved-period strong {
  display: grid;
  gap: 0.15rem;
  min-width: 0;
  padding: 0.55rem;
  border-radius: 0.4rem;
  background: #ffffff;
  color: $oxd-primary-one-color;
  font-size: 0.95rem;
  line-height: 1.15;
}

.ca-vacation-card__approved-period small {
  color: $oxd-interface-gray-color;
  font-size: 0.7rem;
  font-weight: 600;
}

.ca-vacation-card__approved-period p {
  margin: 0.65rem 0 0;
  padding: 0.55rem 0.65rem;
  border-radius: 0.4rem;
  background: #fff7df;
  color: #956000;
  font-size: 0.76rem;
  font-weight: 700;
}

.ca-vacation-card__reasons {
  display: grid;
  gap: 0.35rem;
  margin-top: 0.8rem;
}

.ca-vacation-card__reasons span {
  padding: 0.35rem 0.5rem;
  border-radius: 0.35rem;
  background: $oxd-background-pastel-white-color;
  color: $oxd-interface-gray-color;
  font-size: 0.75rem;
}

.ca-vacation-card__edit {
  width: 100%;
  margin-top: 0.8rem;
  min-height: 2.3rem;
  border: 0;
  border-radius: 0.5rem;
  background: $oxd-primary-one-color;
  color: $oxd-white-color;
  cursor: pointer;
}

.ca-vacation-card__edit.--open {
  background: $oxd-interface-gray-darken-1-color;
}

.ca-preference-editor {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid $oxd-interface-gray-lighten-2-color;
}

.ca-preference-editor__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.ca-preference-editor__header div {
  display: grid;
  gap: 0.2rem;
}

.ca-preference-editor__header span {
  color: $oxd-interface-gray-color;
  font-size: 0.75rem;
}

.ca-preference-editor__header button {
  border: 0;
  background: transparent;
  color: $oxd-primary-one-color;
  cursor: pointer;
}

.ca-preference-editor__fields {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
  gap: 0.75rem;
}

.ca-preference-editor__fields label {
  display: grid;
  gap: 0.3rem;
  color: $oxd-interface-gray-color;
  font-size: 0.75rem;
}

.ca-preference-editor__fields input,
.ca-preference-editor__fields select {
  min-height: 2.4rem;
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 0.45rem;
  padding: 0 0.6rem;
}

.ca-preference-editor__actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  align-items: center;
  margin-top: 0.9rem;
}

.ca-preference-editor__actions > button {
  min-height: 2.25rem;
  padding: 0 1rem;
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 0.45rem;
  background: $oxd-white-color;
  color: $oxd-interface-gray-darken-1-color;
  cursor: pointer;
}

@media (max-width: 48rem) {
  .ca-vacation-planning__header {
    flex-direction: column;
  }

  .ca-vacation-planning__score {
    text-align: left;
  }

  .ca-vacation-planning__tabs {
    width: 100%;
  }

  .ca-vacation-table {
    display: grid;
    gap: 0.75rem;
    border: 0;
    background: transparent;
  }

  .ca-vacation-table__head {
    display: none;
  }

  .ca-vacation-table__row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid $oxd-interface-gray-lighten-2-color;
    border-radius: 0.5rem;
    background: $oxd-white-color;
  }

  .ca-vacation-table__row span:nth-of-type(1)::before {
    content: 'Cargo: ';
    font-weight: 700;
  }

  .ca-vacation-table__row span:nth-of-type(2)::before {
    content: 'Setor: ';
    font-weight: 700;
  }

  .ca-vacation-table__row span:nth-of-type(3)::before {
    content: 'Ferias: ';
    font-weight: 700;
  }

  .ca-vacation-table__row span:nth-of-type(4)::before {
    content: 'Preferencia: ';
    font-weight: 700;
  }

  .ca-vacation-card__approved-period > div {
    grid-template-columns: 1fr;
  }
}
</style>
