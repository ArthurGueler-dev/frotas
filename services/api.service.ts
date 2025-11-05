import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, map } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  // URL base para os endpoints PHP
  private baseUrl = 'https://floripa.in9automacao.com.br';

  constructor(private http: HttpClient) { }

  /**
   * Busca todos os checklists
   * @param limite Número máximo de registros (padrão: 100)
   */
  buscarTodos(limite: number = 100): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'todos')
      .set('limite', limite.toString());

    return this.http.get<any[]>(`${this.baseUrl}/veicular_get.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca checklist por ID
   * @param id ID do checklist
   */
  buscarPorId(id: number): Observable<any> {
    const params = new HttpParams()
      .set('acao', 'id')
      .set('id', id.toString());

    return this.http.get<any>(`${this.baseUrl}/veicular_get.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca checklists por placa
   * @param placa Placa do veículo
   */
  buscarPorPlaca(placa: string): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'placa')
      .set('placa', placa);

    return this.http.get<any[]>(`${this.baseUrl}/veicular_get.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca checklists por período
   * @param dataInicio Data inicial (formato: YYYY-MM-DD)
   * @param dataFim Data final (formato: YYYY-MM-DD)
   */
  buscarPorPeriodo(dataInicio: string, dataFim: string): Observable<any[]> {
    const params = new HttpParams()
      .set('acao', 'periodo')
      .set('data_inicio', dataInicio)
      .set('data_fim', dataFim);

    return this.http.get<any[]>(`${this.baseUrl}/veicular_get.php`, { params })
      .pipe(
        catchError(this.handleError)
      );
  }

  /**
   * Busca dados completos de um checklist (usado para relatórios)
   * @param id ID do checklist
   */
  buscarCompleto(id: number): Observable<any> {
    const params = new HttpParams()
      .set('acao', 'completo')
      .set('id', id.toString());

    return this.http.get<any>(`${this.baseUrl}/veicular_get.php`, { params })
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

    console.error('Erro na API:', errorMessage, error);
    return throwError(() => ({
      error: error.error || {},
      message: errorMessage,
      status: error.status,
      statusText: error.statusText
    }));
  }
}

