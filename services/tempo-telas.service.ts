import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class TempoTelasService {
  // URL base para os endpoints PHP
  private baseUrl = 'https://floripa.in9automacao.com.br';

  constructor(private http: HttpClient) { }

  /**
   * Busca tempos de tela por inspeção
   * @param inspecaoId ID da inspeção
   */
  buscarPorInspecao(inspecaoId: number): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'inspecao')
      .set('inspecao_id', inspecaoId.toString());

    return this.http.get<any[]>(`${this.baseUrl}/veicular_tempotelas.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca tempos de tela por usuário
   * @param usuarioId ID do usuário
   */
  buscarPorUsuario(usuarioId: number): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'usuario')
      .set('usuario_id', usuarioId.toString());

    return this.http.get<any[]>(`${this.baseUrl}/veicular_tempotelas.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca estatísticas de tempos de tela
   */
  buscarEstatisticas(): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'estatisticas');

    return this.http.get<any[]>(`${this.baseUrl}/veicular_tempotelas.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca todos os tempos de tela
   * @param limite Número máximo de registros (padrão: 100)
   */
  buscarTodos(limite: number = 100): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'todos')
      .set('limite', limite.toString());

    return this.http.get<any[]>(`${this.baseUrl}/veicular_tempotelas.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Salva um tempo de tela
   * @param dados Dados do tempo de tela
   */
  salvar(dados: {
    inspecao_id?: number;
    usuario_id?: number;
    tela: string;
    tempo_segundos: number;
    data_hora_inicio: string;
    data_hora_fim: string;
  }): Observable<any> {
    return this.http.post<any>(`${this.baseUrl}/veicular_tempotelas.php`, dados)
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Tratamento de erros HTTP
   */
  private handleError = (error: HttpErrorResponse) => {
    let errorMessage = 'Erro desconhecido';
    
    if (error.error instanceof ErrorEvent) {
      // Erro do lado do cliente
      errorMessage = `Erro: ${error.error.message}`;
    } else {
      // Erro do lado do servidor
      if (error.error && error.error.erro) {
        errorMessage = error.error.erro;
      } else if (error.error && error.error.message) {
        errorMessage = error.error.message;
      } else {
        errorMessage = `Erro ${error.status}: ${error.statusText}`;
      }
    }

    console.error('Erro na API de Tempos de Telas:', errorMessage, error);
    return throwError(() => ({
      error: error.error || {},
      message: errorMessage,
      status: error.status,
      statusText: error.statusText
    }));
  }
}

