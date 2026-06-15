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
    <div v-else-if="plans.length === 0" class="ca-vacation-planning__empty">
      Nenhum colaborador encontrado para planejar.
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
          <span>Inicio</span>
          <strong>{{ formatDate(plan.employee.joinedDate) }}</strong>
          <span>Vencimento maximo</span>
          <strong>{{ formatDate(plan.legal?.concessionEnd) }}</strong>
          <span>Preferencia atendida</span>
          <strong>{{ plan.recommendation?.matchedPreference || 'Alternativa' }}</strong>
          <span>Periodo recomendado</span>
          <strong>{{ recommendationText(plan) }}</strong>
        </div>

        <div class="ca-vacation-card__reasons">
          <span v-for="reason in plan.reasons.slice(0, 3)" :key="reason">
            {{ reason }}
          </span>
        </div>

        <button
          type="button"
          class="ca-vacation-card__edit"
          @click="openPreference(plan)"
        >
          Preferencias
        </button>
      </div>
    </div>

    <div v-if="editingPlan" class="ca-preference-editor">
      <div class="ca-preference-editor__header">
        <strong>{{ editingPlan.employee.name }}</strong>
        <button type="button" @click="editingPlan = null">Fechar</button>
      </div>
      <div class="ca-preference-editor__fields">
        <label>
          Opcao A inicio
          <input v-model="preferenceForm.optionA.fromDate" type="date" />
        </label>
        <label>
          Opcao A fim
          <input v-model="preferenceForm.optionA.toDate" type="date" />
        </label>
        <label>
          Opcao B inicio
          <input v-model="preferenceForm.optionB.fromDate" type="date" />
        </label>
        <label>
          Opcao B fim
          <input v-model="preferenceForm.optionB.toDate" type="date" />
        </label>
        <label>
          Opcao C inicio
          <input v-model="preferenceForm.optionC.fromDate" type="date" />
        </label>
        <label>
          Opcao C fim
          <input v-model="preferenceForm.optionC.toDate" type="date" />
        </label>
        <label>
          Mes restrito
          <select v-model="preferenceForm.restrictedMonth">
            <option :value="null">Sem restricao</option>
            <option v-for="month in months" :key="month.id" :value="month.id">
              {{ month.label }}
            </option>
          </select>
        </label>
      </div>
      <div class="ca-preference-editor__actions">
        <oxd-button
          label="Salvar preferencias"
          display-type="secondary"
          @click="savePreference"
        />
      </div>
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
      meta: {
        aboveNineRate: 0,
      },
      editingPlan: null,
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
      return this.plans.slice(0, 8);
    },
  },
  mounted() {
    this.loadPlanning();
  },
  methods: {
    loadPlanning() {
      this.isLoading = true;
      const http = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/leave/vacation-planning',
      );
      http
        .getAll({limit: 0})
        .then(({data}) => {
          this.plans = data.data || [];
          this.meta = data.meta || {aboveNineRate: 0};
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    openPreference(plan) {
      this.editingPlan = plan;
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
    savePreference() {
      if (!this.editingPlan) return;
      const http = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/leave/vacation-preferences',
      );
      http
        .update(this.editingPlan.employee.empNumber, this.preferenceForm)
        .then(() => {
          this.editingPlan = null;
          this.loadPlanning();
        });
    },
    copyOption(option) {
      return {
        fromDate: option.fromDate,
        toDate: option.toDate,
      };
    },
    recommendationText(plan) {
      if (!plan.recommendation) return 'Sem recomendacao acionavel';
      return `${this.formatDate(plan.recommendation.fromDate)} ate ${this.formatDate(
        plan.recommendation.toDate,
      )}`;
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

.ca-preference-editor {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid $oxd-interface-gray-lighten-2-color;
}

.ca-preference-editor__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
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
  justify-content: flex-end;
  margin-top: 0.9rem;
}

@media (max-width: 48rem) {
  .ca-vacation-planning__header {
    flex-direction: column;
  }

  .ca-vacation-planning__score {
    text-align: left;
  }
}
</style>
