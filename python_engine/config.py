import os

# ─── Supabase Credentials ─────────────────────────────────────────────────────
SUPABASE_URL = "https://ovbzrzyoftchyrkzwezj.supabase.co"
SUPABASE_SERVICE_KEY = (
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9"
    ".eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im92YnpyenlvZnRjaHlya3p3ZXpqIiwicm9sZSI6"
    "InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NzAyNjg2OCwiZXhwIjoyMDkyNjAyODY4fQ"
    ".GVcYacHQ1E7dKCQTcEuE9_XkrIsrKGxVG8LalBrAmBQ"
)

# ─── PostgreSQL Direct Connection (for pandas read_sql) ──────────────────────
DB_HOST     = "db.ovbzrzyoftchyrkzwezj.supabase.co"
DB_PORT     = 5432
DB_NAME     = "postgres"
DB_USER     = "postgres"
DB_PASSWORD = "TA_bryanjeldy123"
DB_URL      = (
    f"postgresql://{DB_USER}:{DB_PASSWORD}"
    f"@{DB_HOST}:{DB_PORT}/{DB_NAME}?sslmode=require"
)

# ─── File Paths ───────────────────────────────────────────────────────────────
BASE_DIR          = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ARTICLE_CSV       = os.environ.get('OVERRIDE_ARTICLE_CSV',      os.path.join(BASE_DIR, "article_dataset5.csv"))
INTERACTIONS_CSV  = os.environ.get('OVERRIDE_INTERACTIONS_CSV', os.path.join(BASE_DIR, "acu_interactions_customized5.csv"))
SAVED_MODELS_DIR  = os.path.join(os.path.dirname(os.path.abspath(__file__)), "saved_models")

# Create saved_models directory if not exists
os.makedirs(SAVED_MODELS_DIR, exist_ok=True)

# ─── Recommendation Settings ─────────────────────────────────────────────────
TOP_K = 5
