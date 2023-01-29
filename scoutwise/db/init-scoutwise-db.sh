#!/bin/bash
set -e

#psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL

psql -v --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
CREATE DATABASE scoutwise;
GRANT ALL PRIVILEGES ON DATABASE scoutwise TO $POSTGRES_USER;
EOSQL

psql -v --username "$POSTGRES_USER" --dbname "scoutwise" <<-EOSQL
--
-- Name: cardcategories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cardcategories (
    merchant_category text,
    accounting_category text,
    id integer NOT NULL
);


ALTER TABLE public.cardcategories OWNER TO postgres;

--
-- Name: cardcategories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.cardcategories ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cardcategories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cardholders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cardholders (
    id integer NOT NULL,
    name text,
    cardname text,
    email text,
    private_email text,
    unit_id integer,
    unit_name text,
    discord_hook text
);


ALTER TABLE public.cardholders OWNER TO postgres;

--
-- Name: cardholders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.cardholders ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cardholders_id_seq
    START WITH 173
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cardtransactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cardtransactions (
    date timestamp without time zone,
    amount_sek double precision,
    fees double precision,
    amount_local double precision,
    local_currency text,
    merchant text,
    merchant_category text,
    city text,
    country text,
    cardholder text,
    to_amount double precision,
    to_amount_currency text,
    from_amount double precision,
    from_amount_currency text,
    exchangerate double precision,
    reference text NOT NULL,
    attachment text,
    note text DEFAULT ''::text,
    admin_note text DEFAULT ''::text NOT NULL,
    accounting_category text DEFAULT ''::text,
    exported boolean DEFAULT false,
    id integer NOT NULL,
    unit_name text,
    name text,
    missingreceipt integer,
    accounting_category_override text DEFAULT ''::text,
    unit_id integer,
    type text,
    unique_reference text NOT NULL
);


ALTER TABLE public.cardtransactions OWNER TO postgres;

--
-- Name: cardtransactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.cardtransactions ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cardtransactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: discord_hooks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.discord_hooks (
    id integer NOT NULL,
    unit_name text,
    discord_hook text
);


ALTER TABLE public.discord_hooks OWNER TO postgres;

--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username text,
    phash text,
    email text,
    fullname text,
    shash text
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.users ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cardholders cardholders_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cardholders
    ADD CONSTRAINT cardholders_pkey PRIMARY KEY (id);


--
-- Name: discord_hooks discord_hooks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discord_hooks
    ADD CONSTRAINT discord_hooks_pkey PRIMARY KEY (id);


--
-- Name: cardtransactions pkey_unique_reference; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cardtransactions
    ADD CONSTRAINT pkey_unique_reference PRIMARY KEY (unique_reference);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- PostgreSQL database dump complete
--

EOSQL
