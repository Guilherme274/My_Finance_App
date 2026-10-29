# 💸 Gestão Financeira Pessoal (PC + Android APK)

Aplicação **Laravel 13 + Blade + Livewire + SQLite** feita para uso diário no **celular Android (como APK offline)** e no **computador Windows**, sem necessidade de hospedagem paga.

- **No PC (`APP_ROLE=hub`)**: roda localmente via `iniciar.bat`, expõe a API de sincronização na sua rede Wi-Fi e no **Tailscale**, e conversa com a API do **Mercado Pago**.
- **No celular (`APP_ROLE=client`)**: o mesmo projeto empacotado em APK via **NativePHP Mobile** (PHP + SQLite embarcados no aparelho). Funciona 100% offline e sincroniza quando encontrar o PC no Wi-Fi ou no Tailscale.

---

## 1. Como abrir no PC (atalho `.bat`)

Dê dois cliques em **[`iniciar.bat`](./iniciar.bat)** (você pode enviar um atalho dele para a Área de Trabalho).

O script automaticamente:
1. Cria o banco `database/database.sqlite` e aplica as migrations/categorias padrão;
2. Compila os estilos (na primeira execução);
3. Mostra no terminal o **Token** e os **IPs do PC** (Wi-Fi e Tailscale) para parear o celular;
4. Abre `http://localhost:8000` no navegador e mantém o servidor ativo.

> **Dica de segurança:** como o servidor escuta na rede local/Tailscale (`0.0.0.0:8000`), defina um PIN no `.env` do PC (`APP_PIN=1234`).

---

## 2. Importar planilha CSV do Nubank (e outros bancos)

No menu **📥 Importar**:
1. Escolha o modelo **Nubank - Cartão de crédito** (já configurado para o formato `date,title,amount`, ex.: `2026-09-16,Giani Buffet,"12,00"`, invertendo o sinal dos gastos e identificando parcelas como `Parcela 7/12`) ou **Nubank - Conta**;
2. Selecione o arquivo `.csv` e clique em **Pré-visualizar**;
3. As **regras de categorização automática** já sugerem a categoria (iFood → Alimentação, Oxxo → Mercado, 99app → Transporte, Google One / Globoplay → Assinaturas, Bluefit → Saúde, Unisant → Educação, Cobasi → Pets, IOF/Multa → Tarifas e juros, Pagamento recebido → Transferências). Você pode ajustar qualquer linha antes de confirmar;
4. Se importar o mesmo arquivo de novo, os lançamentos já existentes aparecem marcados com ♻️ e **não são duplicados**;
5. Qualquer importação pode ser **desfeita com 1 clique**.

Novas regras podem ser criadas em **🏷️ Categorias** → clicar numa categoria → **Regras de categorização automática**.

---

## 3. Integração com a API do Mercado Pago

1. Acesse <https://www.mercadopago.com.br/developers/panel/app>, crie/abra uma aplicação e copie o **Access Token** em **Credenciais de produção** (`APP_USR-...`);
2. No arquivo [`.env`](./.env) do **PC**, preencha:
   ```env
   MERCADOPAGO_ACCESS_TOKEN=APP_USR-...
   MERCADOPAGO_ACCOUNT_NAME="Mercado Pago"
   ```
   *(Esse token é removido automaticamente pelo `config/nativephp.php` ao gerar o APK — ele nunca vai para o celular.)*
3. Pela interface web, abra **🤝 Mercado Pago** → **Testar conexão** → **Importar** (ou via terminal: `php artisan mercadopago:test` e `php artisan mercadopago:import --days=30`).

Os pagamentos aprovados (recebidos já pelo valor líquido e enviados) entram na conta **Mercado Pago** e nunca duplicam (`external_id = mp:{id}`).

---

## 4. Sincronização Celular ↔ PC (Wi-Fi + Tailscale)

1. **Para usar também fora de casa:** instale o [Tailscale](https://tailscale.com/download) (gratuito) no PC e no Android e entre na mesma conta;
2. No PC, abra **🔄 Sincronizar** (ou veja a janela do `iniciar.bat`) e copie os **endereços** e o **token**;
3. No celular, abra **🔄 Sincronizar** e informe um endereço por linha (Wi-Fi primeiro, Tailscale na linha de baixo):
   ```text
   http://192.168.x.x:8000
   http://100.x.y.z:8000
   ```
4. Toque em **🔄 Sincronizar agora**: o app tenta o Wi-Fi (rápido em casa) e, se não responder, usa automaticamente o IP do Tailscale. Conflitos são resolvidos por *last-write-wins* (`updated_at`) e exclusões são propagadas.

Na mesma tela há também **Backup manual (JSON)** para exportar/restaurar sem rede.

---

## 5. Como rodar / gerar o APK no Android

### Opção rápida (sem instalar Android Studio): NativePHP Jump
1. Instale o app **NativePHP Jump** na Play Store do celular;
2. No PC, dê dois cliques em **[`testar-celular.bat`](./testar-celular.bat)** e escaneie o QR Code.

### Gerar e instalar o APK independente
Como identificado no diagnóstico, este PC ainda não tinha o Java/SDK Android instalados. Instale uma única vez:
1. **JDK 17** ([Adoptium Temurin 17](https://adoptium.net/)) e **Android Studio** (com Android SDK Platform 36 e Build-Tools);
2. **7-Zip** em `C:\Program Files\7-Zip\7z.exe` (usado pelo empacotador do NativePHP no Windows);
3. No celular Android, ative **Opções do desenvolvedor → Depuração USB** e conecte no USB;
4. Dê dois cliques em **[`gerar-apk.bat`](./gerar-apk.bat)** (executa `php artisan native:install` na primeira vez e depois `php artisan native:run`).
   Para gerar um APK assinado de distribuição: `php artisan native:package`.
