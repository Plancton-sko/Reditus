const WA_NUMBER = "5541996445869";

const services = {
  "solucao-essencial": "Solução Essencial",
  "solucao-estrategica": "Solução Estratégica",
  "solucao-premium": "Solução Premium",
  "abertura-empresa": "Abertura de Empresa",
  "troca-contador": "Troca de Contador",
  "simples-nacional": "Contabilidade Simples Nacional",
  "lucro-presumido": "Contabilidade Lucro Presumido",
  "deixar-mei": "Deixar de ser MEI",
  "reforma-tributaria": "Consultoria Reforma Tributária",
  outros: "Outros Serviços",
};

/* =========================================
   WHATSAPP
   ========================================= */

function waUrl(data = {}) {
  const lines = [
    "Olá, Reditus Contábil! Gostaria de receber uma proposta personalizada.",
    data.name && `Nome: ${data.name}`,
    data.company && `Empresa: ${data.company}`,
    data.service &&
      `Interesse: ${services[data.service] || data.service}`,
    data.phone && `Telefone: ${data.phone}`,
    data.message && `Detalhes: ${data.message}`,
    "",
    "Podem continuar meu atendimento por aqui, por favor?",
  ].filter(Boolean);

  return `https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(
    lines.join("\n")
  )}`;
}

/* =========================================
   LINKS PARA WHATSAPP
   ========================================= */

document.querySelectorAll("[data-wa]").forEach((a) => {
  a.href = waUrl();
});

/* =========================================
   BOTÕES DE PROPOSTA
   ========================================= */

document.querySelectorAll("[data-proposal]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const service = btn.dataset.proposal || "outros";
    const select = document.querySelector("#service");

    if (select) {
      select.value = service;
    }

    document
      .querySelector("#contato")
      ?.scrollIntoView({
        behavior: "smooth",
      });

    setTimeout(() => {
      document.querySelector("#name")?.focus();
    }, 600);
  });
});

/* =========================================
   MENU MOBILE
   ========================================= */

const menu = document.querySelector(".mobile-toggle");

menu?.addEventListener("click", () => {
  document.querySelector(".navlinks")?.classList.toggle("open");
});

document.querySelectorAll(".navlinks a").forEach((a) => {
  a.addEventListener("click", () => {
    document.querySelector(".navlinks")?.classList.remove("open");
  });
});

/* =========================================
   FORMULÁRIO DE PROPOSTA
   ========================================= */

const form = document.querySelector("#proposal-form");

if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const btn = form.querySelector('button[type="submit"]');
    const status = form.querySelector(".status");
    const fd = new FormData(form);

    const pref = fd.get("responsePreference");

    /* Estado inicial do botão */
    btn.disabled = true;
    btn.textContent = "Enviando...";
    status.className = "status";

    try {
      /* =====================================
         PREPARAÇÃO DOS DADOS
         ===================================== */

      const body = new URLSearchParams();

      fd.forEach((value, key) => {
        body.append(key, String(value));
      });

      body.append("source", "site-estatico-hostgator");

      /* =====================================
         ENVIO PARA O BACKEND
         ===================================== */

      const response = await fetch("/api/contact.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: body.toString(),
      });

      if (!response.ok) {
        throw new Error("Erro ao enviar formulário.");
      }

      /* =====================================
         MENSAGEM DE SUCESSO
         ===================================== */

      status.textContent =
        pref === "whatsapp"
          ? "Solicitação registrada. Abrindo o WhatsApp para continuar o atendimento..."
          : "Solicitação registrada. Nossa equipe responderá pelo e-mail informado.";

      status.className = "status ok show";

      /* =====================================
         REDIRECIONAMENTO PARA WHATSAPP
         ===================================== */

      if (pref === "whatsapp") {
        const data = Object.fromEntries(fd.entries());

        setTimeout(() => {
          window.location.assign(waUrl(data));
        }, 650);
      } else {
        form.reset();
      }
    } catch (err) {
      /* =====================================
         TRATAMENTO DE ERRO
         ===================================== */

      status.textContent =
        "Não foi possível registrar a solicitação. Use o botão do WhatsApp ou tente novamente.";

      status.className = "status err show";
    } finally {
      /* =====================================
         RESTAURAÇÃO DO BOTÃO
         ===================================== */

      btn.disabled = false;

      btn.textContent =
        pref === "whatsapp"
          ? "Solicitar proposta pelo WhatsApp"
          : "Solicitar proposta por e-mail";
    }
  });

  /* =========================================
     PREFERÊNCIA DE ATENDIMENTO
     ========================================= */

  const radios = form.querySelectorAll(
    'input[name="responsePreference"]'
  );

  const updateButton = () => {
    const value = form.querySelector(
      'input[name="responsePreference"]:checked'
    )?.value;

    const button = form.querySelector(
      'button[type="submit"]'
    );

    button.className =
      "btn " +
      (value === "whatsapp"
        ? "btn-whatsapp"
        : "btn-primary");

    button.style.width = "100%";

    button.textContent =
      value === "whatsapp"
        ? "Solicitar proposta pelo WhatsApp"
        : "Solicitar proposta por e-mail";
  };

  radios.forEach((radio) => {
    radio.addEventListener("change", updateButton);
  });

  updateButton();
}