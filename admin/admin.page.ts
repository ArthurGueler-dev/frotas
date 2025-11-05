import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../services/api.service';
import { TempoTelasService } from '../services/tempo-telas.service';
import { AlertController } from '@ionic/angular';

interface Checklist {
  id: number;
  placa: string;
  km_inicial: number;
  nivel_combustivel: string;
  data_realizacao: string;
  status_geral?: string;
  usuario_nome?: string;
  observacao_painel?: string;
}

@Component({
  selector: 'app-admin',
  templateUrl: './admin.page.html',
  styleUrls: ['./admin.page.scss'],
  standalone: false,
})
export class AdminPage implements OnInit {
  checklists: Checklist[] = [];
  checklistsFiltrados: Checklist[] = [];
  carregando = false;
  erro = '';

  // Filtros
  filtroPlaca = '';
  filtroDataInicio = '';
  filtroDataFim = '';

  // Estatísticas
  totalChecklists = 0;
  checklistsHoje = 0;
  checklistsSemana = 0;

  constructor(
    private router: Router,
    private apiService: ApiService,
    private tempoTelasService: TempoTelasService,
    private alertController: AlertController
  ) { }

  ngOnInit() {
    this.carregarChecklists();
  }

  carregarChecklists() {
    this.carregando = true;
    this.erro = '';

    this.apiService.buscarTodos(1000).subscribe({
      next: (response) => {
        this.checklists = response;
        this.checklistsFiltrados = response;
        this.carregando = false;
        this.calcularEstatisticas();
        console.log('Checklists carregados:', this.checklists);
      },
      error: (error) => {
        console.error('Erro ao carregar checklists:', error);
        const mensagemErro = error.error?.erro || error.error?.message || error.message || 'Erro desconhecido';
        const detalhes = error.error?.detalhes || error.statusText || '';
        this.erro = `Erro ao carregar histórico: ${mensagemErro}${detalhes ? ' - ' + detalhes : ''}`;
        this.carregando = false;
      }
    });
  }

  calcularEstatisticas() {
    this.totalChecklists = this.checklists.length;

    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    const inicioSemana = new Date();
    inicioSemana.setDate(hoje.getDate() - 7);
    inicioSemana.setHours(0, 0, 0, 0);

    this.checklistsHoje = this.checklists.filter(c => {
      const data = new Date(c.data_realizacao);
      data.setHours(0, 0, 0, 0);
      return data.getTime() === hoje.getTime();
    }).length;

    this.checklistsSemana = this.checklists.filter(c => {
      const data = new Date(c.data_realizacao);
      return data >= inicioSemana;
    }).length;
  }

  aplicarFiltros() {
    this.checklistsFiltrados = this.checklists.filter(checklist => {
      // Filtro por placa
      const passaPlaca = !this.filtroPlaca ||
        checklist.placa.toLowerCase().includes(this.filtroPlaca.toLowerCase());

      // Filtro por data inicial
      const passaDataInicio = !this.filtroDataInicio ||
        new Date(checklist.data_realizacao) >= new Date(this.filtroDataInicio);

      // Filtro por data final
      const passaDataFim = !this.filtroDataFim ||
        new Date(checklist.data_realizacao) <= new Date(this.filtroDataFim);

      return passaPlaca && passaDataInicio && passaDataFim;
    });
  }

  buscarPorPlaca(event: any) {
    this.filtroPlaca = event.target.value;
    this.aplicarFiltros();
  }

  limparFiltros() {
    this.filtroPlaca = '';
    this.filtroDataInicio = '';
    this.filtroDataFim = '';
    this.checklistsFiltrados = this.checklists;
  }

  checklistDetalhado: any = null;
  mostrarModal = false;
  temposTelas: any[] = [];

  // Controle da foto expandida
  fotoExpandida: string | null = null;
  mostrarFotoExpandida = false;
  zoomLevel = 1;

  async verDetalhes(checklist: Checklist) {
    this.carregando = true;
    try {
      // Busca todos os detalhes do checklist usando o endpoint 'completo'
      this.apiService.buscarCompleto(checklist.id).subscribe({
        next: (dados) => {
          this.checklistDetalhado = dados;

          // Busca os tempos de tela para esta inspeção
          this.tempoTelasService.buscarPorInspecao(checklist.id).subscribe({
            next: (tempos) => {
              this.temposTelas = tempos;
              console.log('Tempos de telas:', tempos);
            },
            error: (error) => {
              console.error('Erro ao buscar tempos de telas:', error);
              this.temposTelas = [];
            }
          });

          this.mostrarModal = true;
          this.carregando = false;
          console.log('Detalhes completos:', dados);
        },
        error: async (error) => {
          console.error('Erro ao carregar detalhes:', error);
          this.carregando = false;

          // Mostra erro detalhado antes do fallback
          const mensagemErro = error.error?.erro || error.error?.message || error.message || 'Erro desconhecido';
          const detalhes = error.error?.detalhes || error.statusText || '';

          const alert = await this.alertController.create({
            header: 'Erro ao Carregar Detalhes',
            message: `
              <strong>Erro:</strong> ${mensagemErro}<br>
              ${detalhes ? `<strong>Detalhes:</strong> ${detalhes}` : ''}
            `,
            buttons: [
              {
                text: 'Ver Dados Básicos',
                handler: () => {
                  this.mostrarAlertBasico(checklist);
                }
              },
              'Fechar'
            ]
          });

          await alert.present();
        }
      });
    } catch (error) {
      console.error('Erro:', error);
      this.carregando = false;
      this.mostrarAlertBasico(checklist);
    }
  }

  async mostrarAlertBasico(checklist: Checklist) {
    const alert = await this.alertController.create({
      header: `Checklist - ${checklist.placa}`,
      message: `
        <strong>ID:</strong> ${checklist.id}<br>
        <strong>Placa:</strong> ${checklist.placa}<br>
        <strong>KM Inicial:</strong> ${checklist.km_inicial}<br>
        <strong>Combustível:</strong> ${checklist.nivel_combustivel}<br>
        <strong>Data:</strong> ${this.formatarData(checklist.data_realizacao)}
      `,
      buttons: ['OK']
    });
    await alert.present();
  }

  fecharModal() {
    this.mostrarModal = false;
    this.checklistDetalhado = null;
    this.temposTelas = [];
  }

  // Métodos para expansão de fotos
  expandirFoto(foto: string) {
    this.fotoExpandida = foto;
    this.mostrarFotoExpandida = true;
    this.zoomLevel = 1;
  }

  fecharFotoExpandida() {
    this.mostrarFotoExpandida = false;
    this.fotoExpandida = null;
    this.zoomLevel = 1;
  }

  zoomIn() {
    if (this.zoomLevel < 5) {
      this.zoomLevel += 0.5;
    }
  }

  zoomOut() {
    if (this.zoomLevel > 0.5) {
      this.zoomLevel -= 0.5;
    }
  }

  resetZoom() {
    this.zoomLevel = 1;
  }

  formatarData(data: string): string {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleString('pt-BR');
  }

  recarregar(event: any) {
    this.apiService.buscarTodos(1000).subscribe({
      next: (response) => {
        this.checklists = response;
        this.aplicarFiltros();
        this.calcularEstatisticas();
        event.target.complete();
      },
      error: (error) => {
        console.error('Erro ao recarregar:', error);
        event.target.complete();
      }
    });
  }

  voltar() {
    this.router.navigate(['/']);
  }

  getCorStatus(valor: string): string {
    if (!valor) return 'medium';

    const valorLower = valor.toLowerCase();

    if (valorLower.includes('bom') || valorLower.includes('ótimo') || valorLower.includes('otimo')) {
      return 'success';
    } else if (valorLower.includes('ruim') || valorLower.includes('crítico') || valorLower.includes('critico')) {
      return 'danger';
    } else if (valorLower.includes('regular') || valorLower.includes('atenção') || valorLower.includes('atencao')) {
      return 'warning';
    } else if (valorLower.includes('satisfatório') || valorLower.includes('satisfatorio')) {
      return 'primary';
    }

    return 'medium';
  }

  formatarTempo(segundos: number): string {
    if (!segundos || segundos === 0) return '0s';

    const minutos = Math.floor(segundos / 60);
    const segs = segundos % 60;

    if (minutos === 0) {
      return `${segs}s`;
    } else if (segs === 0) {
      return `${minutos}m`;
    } else {
      return `${minutos}m ${segs}s`;
    }
  }

  getTotalTempo(): string {
    if (!this.temposTelas || this.temposTelas.length === 0) {
      return '0s';
    }

    const totalSegundos = this.temposTelas.reduce((total, tempo) => {
      return total + (tempo.tempo_segundos || 0);
    }, 0);

    return this.formatarTempo(totalSegundos);
  }

  getNomeTela(nomeTela: string): string {
    const nomes: { [key: string]: string } = {
      'inspecao-inicial': 'Inspeção Inicial',
      'inspecao-veiculo': 'Inspeção do Veículo',
      'fotos-veiculo': 'Fotos do Veículo',
      'pneus': 'Pneus'
    };
    return nomes[nomeTela] || nomeTela;
  }
}
